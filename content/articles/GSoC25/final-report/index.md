---
title: "2025 Final Report: RISC-V Vector (RVV) Optimization for the dav1d AV1 Decoder"
date: 2025-09-01
---

## Project Overview and Goals

<p><code>dav1d</code> is an open-source AV1 video decoder that aims for
the highest possible performance. The primary goal of this GSoC project
was to optimize <code>dav1d</code>'s {{< footnote "dav1d" "https://code.videolan.org/videolan/dav1d" >}} core video processing functions by
implementing hand-written assembly using the <strong>RISC-V Vector (RVV)
Extension</strong>. The objective was to maximize performance to enable
smooth playback of high-definition AV1 video on low-power RISC-V
devices, thereby demonstrating and enhancing the multimedia capabilities
of the RISC-V ecosystem.</p>

## Key Activities and Achievements

<p>During the project, I performed the following key activities:</p>
<ul>
<li><strong>Building a RISC-V Gentoo Development Environment:</strong>
To facilitate this project, I first built and stabilized a cutting-edge Gentoo Linux development image. This involved using the crossdev-stages {{< footnote "crossdev-stages" "https://github.com/lu-zero/crossdev-stages" >}} scripts to cross-compile the entire system for RISC-V with full RVV support, a process during which I identified, debugged, and contributed fixes for numerous upstream bugs in core packages like GCC, crossdev, and Perl
</li>

<li><strong>Performance Analysis and Bottleneck Identification:</strong>
Using the <code>perf</code> tool, I analyzed <code>dav1d</code>'s performance to identify bottlenecks. The analysis confirmed that functions like <strong><code>prep_8tap</code></strong> and <strong><code>put_8tap</code></strong> were responsible for the most significant computational load.</li>

<li><strong>C-based Code Optimization:</strong>
<ul>
  <li><strong>w_mask C Code Improvement (MR !1804):</strong> While implementing the RVV version, I identified an area in the existing C code that could be optimized. By simply pre-calculating and storing frequently used values in variables, I achieved a meaningful <strong>~7% performance improvement</strong> on an x86_64 CPU.
  </li>
</ul>
</li>

<li><strong>RVV Assembly Optimization:</strong>
Based on the analysis, I implemented and contributed RVV assembly optimizations for the following
core functions. All code was thoroughly tested on <strong>Spacemit K1
(VLEN=256)</strong> and <strong>K230 (VLEN=128)</strong> hardware.
<ul>
<li><strong>w_mask RVV Implementation (MR !1797):</strong> I implemented
RVV assembly for three <code>w_mask</code> functions (<code>444</code>,
<code>422</code>, <code>420</code>). By applying various techniques such
as loop unrolling and dynamic LMUL selection based on VLEN, I achieved a
performance increase of <strong>up to 16x</strong> on Spacemit K1 and
<strong>up to 9x</strong> on K230.</li>
<li><strong>emu_edge (MR !1808):</strong> I optimized the
<code>emu_edge</code> function with RVV, resulting in a performance
increase of <strong>up to 5x</strong>, depending on the input
values.</li>
</ul></li>
</ul>

## Current Project Status

<p>The submitted Merge Requests have successfully accelerated key
<code>dav1d</code> functions using RVV. The optimized code has passed
all <code>checkasm</code> and <code>argon</code> conformance tests,
ensuring its stability. These changes show significant performance gains
across various block widths and on different hardware with VLEN=128 and
VLEN=256.</p>

## Code Contributions & Merge Requests

<p>The following are the main Merge Requests I worked on and submitted
during this GSoC period. You can find detailed code changes, benchmark
results, and the review process at each link.</p>
<ul>
<li><strong>mc: 8bpc rvv w_mask (v1) (!1797):</strong> <a
href="https://code.videolan.org/videolan/dav1d/-/merge_requests/1797">https://code.videolan.org/videolan/dav1d/-/merge_requests/1797</a></li>
<li><strong>mc: 8bpc c w_mask (!1804):</strong> <a
href="https://code.videolan.org/videolan/dav1d/-/merge_requests/1804">https://code.videolan.org/videolan/dav1d/-/merge_requests/1804</a></li>
<li><strong>mc: 8bpc rvv emu_edge (!1808):</strong> <a
href="https://code.videolan.org/videolan/dav1d/-/merge_requests/1808">https://code.videolan.org/videolan/dav1d/-/merge_requests/1808</a></li>
</ul>

## Future Work

<p>While significant progress was made during GSoC, the RISC-V
optimization for <code>dav1d</code> is not yet complete. I plan to
remain active in the community after GSoC and will continue contributing
by addressing the following tasks:</p>
<ul>
<li><strong>Optimize <code>prep_8tap</code> and
<code>put_8tap</code>:</strong> The next goal is to implement RVV
assembly for these two functions, which were identified as the biggest
bottlenecks by <code>perf</code>.</li>
<li><strong>Further Optimization:</strong> I plan to apply further
optimizations, such as eliminating the height-based loop in
<code>w_mask</code> for cases where <code>w*h &lt; 64</code> to process
it in a single pass.</li>
</ul>

## Challenges and Key Learnings

<p>Through this project, I gained a deep understanding of RISC-V vector
assembly and experienced solving complex performance issues on real
hardware. The initial process of understanding RVV was very challenging,
but I finally had a breakthrough during a 15-hour flight to Bulgaria,
where I could focus intensely on the documentation.</p>
<p>This entire journey would have been impossible without my excellent
mentors, <strong>Nathan</strong> and <strong>Luca</strong>, and the
helpful members of the community. I would like to express my sincere
gratitude to everyone who helped me.</p>

## Resources

<p>The following resources were extremely helpful throughout the
project:</p>
<ul>
<li><strong>RISC-V Specifications:</strong>
<ul>
<li><a
href="https://five-embeddev.com/riscv-v-spec/v1.0/v-spec.html#">RISC-V
Vector Specification v1.0</a></li>
<li><a
href="https://five-embeddev.com/riscv-bitmanip/1.0.0/bitmanip.html#insns-max">RISC-V
Bit-Manipulation ISA-Extension</a></li>
<li><a href="https://projectf.io/posts/riscv-cheat-sheet/">RISC-V
Assembly Programmer's Cheat Sheet</a></li>
</ul></li>
<li><strong>RISE Optimization Guide:</strong>
<ul>
<li><a
href="https://riscv-optimization-guide-riseproject-c94355ae3e68722524.gitlab.io/">RISE
Project - RISC-V Optimization Guide</a></li>
</ul></li>
<li><strong>Especially helpful for understanding RVV:</strong>
<ul>
<li><a
href="https://eupilot.eu/wp-content/uploads/2022/11/RISC-V-VectorExtension-1-1.pdf">RISC-V
Vector Extension by EUPILOT</a></li>
<li><a
href="https://0x80.pl/notesen/2024-11-09-riscv-vector-extension.html">A
Gentle Introduction to RISC-V Vector Extension by 0x80.pl</a></li>
<li><a href="https://people.videolan.org/~negge/vdd24.pdf">Optimizing
Software for RISC-V (VDD24) by Nathan Egge</a></li>
<li><a
href="https://people.videolan.org/~unlord/riscv101-2025.pdf">RISC-V 101
(RISC-V Summit EU) by Nathan Egge</a></li>
</ul></li>
</ul>
