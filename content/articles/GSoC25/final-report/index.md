---
title: "2025 Final Report: RISC-V Vector (RVV) Optimization for the dav1d AV1 Decoder"
date: 2025-09-01
tags: [GSoC25, GSoC, RISC-V, dav1d, RVV]
---

## Project Overview and Goals

`dav1d` is an open-source AV1 video decoder that aims for the highest possible performance. The primary goal of this GSoC project was to optimize `dav1d`'s {{< footnote "dav1d" "https://code.videolan.org/videolan/dav1d" >}} core video processing functions by implementing hand-written assembly using the **RISC-V Vector (RVV) Extension**. The objective was to maximize performance to enable smooth playback of high-definition AV1 video on low-power RISC-V devices, thereby demonstrating and enhancing the multimedia capabilities of the RISC-V ecosystem.

## Key Activities and Achievements

During the project, I performed the following key activities:

- **Building a RISC-V Gentoo Development Environment:** To facilitate this project, I first built and stabilized a cutting-edge Gentoo Linux development image. This involved using the crossdev-stages {{< footnote "crossdev-stages" "https://github.com/lu-zero/crossdev-stages" >}} scripts to cross-compile the entire system for RISC-V with full RVV support, a process during which I identified, debugged, and contributed fixes for numerous upstream bugs in core packages like GCC, crossdev, and Perl
- **Performance Analysis and Bottleneck Identification:** Using the `perf` tool, I analyzed `dav1d`'s performance to identify bottlenecks. The analysis confirmed that functions like **`prep_8tap`** and **`put_8tap`** were responsible for the most significant computational load.
- **C-based Code Optimization:**
  - **w_mask C Code Improvement (MR !1804):** While implementing the RVV version, I identified an area in the existing C code that could be optimized. By simply pre-calculating and storing frequently used values in variables, I achieved a meaningful **~7% performance improvement** on an x86_64 CPU.
- **RVV Assembly Optimization:** Based on the analysis, I implemented and contributed RVV assembly optimizations for the following core functions. All code was thoroughly tested on **Spacemit K1 (VLEN=256)** and **K230 (VLEN=128)** hardware.
  - **w_mask RVV Implementation (MR !1797):** I implemented RVV assembly for three `w_mask` functions (`444`, `422`, `420`). By applying various techniques such as loop unrolling and dynamic LMUL selection based on VLEN, I achieved a performance increase of **up to 16x** on Spacemit K1 and **up to 9x** on K230.
  - **emu_edge (MR !1808):** I optimized the `emu_edge` function with RVV, resulting in a performance increase of **up to 5x**, depending on the input values.

## Current Project Status

The submitted Merge Requests have successfully accelerated key `dav1d` functions using RVV. The optimized code has passed all `checkasm` and `argon` conformance tests, ensuring its stability. These changes show significant performance gains across various block widths and on different hardware with VLEN=128 and VLEN=256.

## Code Contributions & Merge Requests

The following are the main Merge Requests I worked on and submitted during this GSoC period. You can find detailed code changes, benchmark results, and the review process at each link.

- **mc: 8bpc rvv w_mask (v1) (!1797):** [https://code.videolan.org/videolan/dav1d/-/merge_requests/1797](https://code.videolan.org/videolan/dav1d/-/merge_requests/1797)
- **mc: 8bpc c w_mask (!1804):** [https://code.videolan.org/videolan/dav1d/-/merge_requests/1804](https://code.videolan.org/videolan/dav1d/-/merge_requests/1804)
- **mc: 8bpc rvv emu_edge (!1808):** [https://code.videolan.org/videolan/dav1d/-/merge_requests/1808](https://code.videolan.org/videolan/dav1d/-/merge_requests/1808)

## Future Work

While significant progress was made during GSoC, the RISC-V optimization for `dav1d` is not yet complete. I plan to remain active in the community after GSoC and will continue contributing by addressing the following tasks:

- **Optimize `prep_8tap` and `put_8tap`:** The next goal is to implement RVV assembly for these two functions, which were identified as the biggest bottlenecks by `perf`.
- **Further Optimization:** I plan to apply further optimizations, such as eliminating the height-based loop in `w_mask` for cases where `w*h < 64` to process it in a single pass.

## Challenges and Key Learnings

Through this project, I gained a deep understanding of RISC-V vector assembly and experienced solving complex performance issues on real hardware. The initial process of understanding RVV was very challenging, but I finally had a breakthrough during a 15-hour flight to Bulgaria, where I could focus intensely on the documentation.

This entire journey would have been impossible without my excellent mentors, **Nathan** and **Luca**, and the helpful members of the community. I would like to express my sincere gratitude to everyone who helped me.

## Resources

The following resources were extremely helpful throughout the project:

- **RISC-V Specifications:**
  - [RISC-V Vector Specification v1.0](https://five-embeddev.com/riscv-v-spec/v1.0/v-spec.html#)
  - [RISC-V Bit-Manipulation ISA-Extension](https://five-embeddev.com/riscv-bitmanip/1.0.0/bitmanip.html#insns-max)
  - [RISC-V Assembly Programmer's Cheat Sheet](https://projectf.io/posts/riscv-cheat-sheet/)
- **RISE Optimization Guide:**
  - [RISE Project - RISC-V Optimization Guide](https://riscv-optimization-guide-riseproject-c94355ae3e68722524.gitlab.io/)
- **Especially helpful for understanding RVV:**
  - [RISC-V Vector Extension by EUPILOT](https://eupilot.eu/wp-content/uploads/2022/11/RISC-V-VectorExtension-1-1.pdf)
  - [A Gentle Introduction to RISC-V Vector Extension by 0x80.pl](https://0x80.pl/notesen/2024-11-09-riscv-vector-extension.html)
  - [Optimizing Software for RISC-V (VDD24) by Nathan Egge](https://people.videolan.org/~negge/vdd24.pdf)
  - [RISC-V 101 (RISC-V Summit EU) by Nathan Egge](https://people.videolan.org/~unlord/riscv101-2025.pdf)
