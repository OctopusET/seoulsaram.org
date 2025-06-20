#!/usr/bin/env python3

import os
import time
import threading
import subprocess
from http.server import ThreadingHTTPServer, SimpleHTTPRequestHandler
from glob import glob

# Configuration
WATCH_PATTERN = "**/*.php"  # Pattern to watch for changes
POLL_INTERVAL = 0.2         # How often to check for file changes (seconds)
DEBOUNCE_TIME = 1.0         # Minimum time between rebuilds (seconds)
GEN_COMMAND = ["php", "gen.php"]  # Command to regenerate site
HTTP_PORT = 8000            # Port for the HTTP server

# Global list of SSE client connections
clients = []

class LiveReloadHandler(SimpleHTTPRequestHandler):
    """HTTP handler with Server-Sent Events support and script injection."""
    
    def do_GET(self):
        """Handle GET requests, including SSE endpoint."""
        if self.path == "/events":
            try:
                self.send_response(200)
                self.send_header("Content-Type", "text/event-stream")
                self.send_header("Cache-Control", "no-cache")
                self.send_header("Connection", "keep-alive")
                self.end_headers()
                
                # Register this client for updates
                clients.append(self.wfile)
                try:
                    # Keep connection open
                    while True:
                        time.sleep(1)
                except (BrokenPipeError, ConnectionResetError):
                    pass
                finally:
                    if self.wfile in clients:
                        clients.remove(self.wfile)
            except (BrokenPipeError, ConnectionResetError):
                # Client disconnected during headers, just return
                pass
        else:
            # Handle normal requests
            try:
                super().do_GET()
            except (BrokenPipeError, ConnectionResetError):
                # Client disconnected, just log it
                print("Client disconnected during response")
    
    def end_headers(self):
        """Add cache control headers to prevent caching."""
        try:
            self.send_header("Cache-Control", "no-store, no-cache, must-revalidate")
            super().end_headers()
        except (BrokenPipeError, ConnectionResetError):
            # Client disconnected during headers
            pass
    
    def copyfile(self, source, outputfile):
        """Inject reload script into HTML files."""
        try:
            if self.path.endswith(".html") or self.path == "/" or self.path.endswith("/"):
                content = source.read()
                try:
                    html = content.decode("utf-8")
                    reload_script = """
<script>
  (function() {
    let reconnectAttempts = 0;
    const maxReconnectAttempts = 5;
    const reconnectDelay = 3000;
    
    function connectEventSource() {
      const evtSource = new EventSource('/events');
      
      evtSource.onmessage = function(event) {
        console.log('Reloading page due to file changes');
        window.location.reload(true);
      };
      
      evtSource.onopen = function() {
        reconnectAttempts = 0;
      };
      
      evtSource.onerror = function() {
        evtSource.close();
        
        if (reconnectAttempts < maxReconnectAttempts) {
          reconnectAttempts++;
          console.log(`SSE connection error, reconnecting in ${reconnectDelay/1000}s... (${reconnectAttempts}/${maxReconnectAttempts})`);
          setTimeout(connectEventSource, reconnectDelay);
        } else {
          console.log('Max reconnection attempts reached');
        }
      };
    }
    
    connectEventSource();
  })();
</script>
"""
                    if "</body>" in html:
                        html = html.replace("</body>", reload_script + "</body>")
                    else:
                        html = html + reload_script
                    
                    outputfile.write(html.encode("utf-8"))
                except UnicodeDecodeError:
                    # If not a text file, write the original content
                    source.seek(0)
                    outputfile.write(source.read())
            else:
                # For non-HTML files, copy as-is
                super().copyfile(source, outputfile)
        except BrokenPipeError:
            # Client disconnected prematurely
            print("Client disconnected prematurely")
        except ConnectionResetError:
            # Similar to BrokenPipeError
            print("Connection reset by client")
        except Exception as e:
            # Log other exceptions but don't crash
            print(f"Error in copyfile: {e}")

class FileWatcher(threading.Thread):
    """Watch for file changes and trigger rebuilds."""
    
    def __init__(self):
        super().__init__(daemon=True)
        self.file_times = {}
        self.last_rebuild = 0
    
    def run(self):
        """Main thread loop to check for file changes."""
        # Initial scan of files
        self.scan_files()
        # Initial build
        self.rebuild()
        
        # Monitoring loop
        while True:
            time.sleep(POLL_INTERVAL)
            if self.check_for_changes():
                self.rebuild()
    
    def scan_files(self):
        """Scan all files matching the watch pattern."""
        for filepath in glob(WATCH_PATTERN, recursive=True):
            try:
                self.file_times[filepath] = os.path.getmtime(filepath)
            except OSError:
                pass
    
    def check_for_changes(self):
        """Check if any watched files have changed."""
        changed = False
        current_files = set(glob(WATCH_PATTERN, recursive=True))
        
        # Check for new or modified files
        for filepath in current_files:
            try:
                mtime = os.path.getmtime(filepath)
                if filepath not in self.file_times:
                    print(f"New file detected: {filepath}")
                    changed = True
                elif mtime > self.file_times[filepath]:
                    print(f"File modified: {filepath}")
                    changed = True
                self.file_times[filepath] = mtime
            except OSError:
                pass
        
        # Check for deleted files
        for filepath in list(self.file_times.keys()):
            if filepath not in current_files:
                print(f"File deleted: {filepath}")
                del self.file_times[filepath]
                changed = True
        
        return changed
    
    def rebuild(self):
        """Rebuild the site if enough time has passed since last rebuild."""
        current_time = time.time()
        if current_time - self.last_rebuild < DEBOUNCE_TIME:
            return
        
        print(f"\n🔄 Regenerating site: {' '.join(GEN_COMMAND)}")
        try:
            result = subprocess.run(GEN_COMMAND, capture_output=True, text=True)
            if result.returncode == 0:
                print("✅ Generation completed successfully")
                if result.stdout.strip():
                    print(result.stdout)
            else:
                print("❌ Generation failed")
                if result.stderr.strip():
                    print(f"Error: {result.stderr}")
        except Exception as e:
            print(f"❌ Error running command: {e}")
        
        self.last_rebuild = current_time
        
        # Notify all connected clients
        self.notify_clients()
    
    def notify_clients(self):
        """Send update notification to all connected SSE clients."""
        print(f"📢 Notifying {len(clients)} connected browsers")
        for client in list(clients):
            try:
                client.write(b"data: update\n\n")
                client.flush()
            except (BrokenPipeError, ConnectionResetError, OSError):
                if client in clients:
                    clients.remove(client)

if __name__ == "__main__":
    # Start file watcher thread
    watcher = FileWatcher()
    watcher.start()
    
    # Start HTTP server
    server = ThreadingHTTPServer(("", HTTP_PORT), LiveReloadHandler)
    print(f"🌐 Server running at http://localhost:{HTTP_PORT}")
    print(f"📂 Watching for changes in {WATCH_PATTERN}")
    print("⏱️  Press Ctrl+C to stop")
    
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        print("\n👋 Server stopped")
