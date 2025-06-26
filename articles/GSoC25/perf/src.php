<?php genheader("How to build perf for spacemit x60 and use it with dav1d", "Jun 27, 2025"); ?>
This time we are going to explore how to use Perf command.
This allows us to determine which functions have the most significant impact on decoding with dav1d.

<?php h("How to build perf"); ?>

<pre><code>cd linux/tools/perf/pmu-events
./jevents.py riscv spacemit/x60 . pmu-events.c
</pre></code>

<p>It will fail, so remove the failing codes in jevents.py.</p>

<pre><code>if len(archs) &lt; 2:
  raise IOError(f&#39;Missing architecture directory \&#39;{_args.arch}\&#39;&#39;)
</pre></code>

<p>Then, (I know some <code>make</code> options are not needed but it just works).</p>

<pre><code>cd ..
make -j 8 V=1 VF=1 HOSTCC=riscv64-unknown-linux-gnu-gcc HOSTLD=riscv64-unknown-linux-gnu-ld CC=riscv64-unknown-linux-gnu-gcc CXX=riscv64-unknown-linux-gnu-g++ AR=riscv64-unknown-linux-gnu-ar LD=riscv64-unknown-linux-gnu-ld NM=riscv64-unknown-linux-gnu-nm PKG_CONFIG=riscv64-unknown-linux-gnu-pkg-config prefix=/usr bindir_relative=bin tipdir=share/doc/perf-6.8 &#39;EXTRA_CFLAGS=-O2 -pipe&#39; &#39;EXTRA_LDFLAGS=-Wl,-O1 -Wl,--as-needed&#39; ARCH=riscv BUILD_BPF_SKEL= BUILD_NONDISTRO=1 JDIR= CORESIGHT= GTK2= feature-gtk2-infobar= NO_AUXTRACE= NO_BACKTRACE= NO_DEMANGLE= NO_JEVENTS=0
</pre></code>

<p><b>Note</b>: Cross compile fails, so just build it on your board. It will take around 10mins on Milk-V jupiter.</p>

<?php h("How to run perf"); ?>
<pre><code>sudo perf record -e u_mode_cycle ls
sudo perf report # You might not need a root permission
</code></pre>

<p>There’s other <code>-e</code> options you can try, Read lu_zeros' article. <?php footnote("lu_zero’s article", " https://dev.to/luzero/bringing-up-bpi-f3-part-25-27o4"); ?></p>

<pre><code>perf record --group -e u_mode_cycle,m_mode_cycle,s_mode_cycle
perf record --group -e alu_inst,amo_inst,atomic_inst,fp_div_inst,fp_inst,fp_load_inst,fp_store_inst,load_inst,lr_inst,mult_inst,sc_inst,store_inst,unaligned_load_inst,unaligned_store_inst
</code></pre>

<?php h("What about dav1d?"); ?>
<p>I used this way:</p>
<pre><code>sudo perf record -e u_mode_cycle dav1d -i sample_video.ivf -o /dev/null</code></pre>

<?php h("Vendor's Official documentation"); ?>
I found this document <?php footnote("Spacemit's Development Guide", "https://bianbu.spacemit.com/en/development/perf/"); ?> on Internet.
