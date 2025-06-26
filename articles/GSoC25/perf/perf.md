# How to build perf for spacemit x60 and use it with dav1d

## How to build perf

```
cd linux/tools/perf/pmu-events
./jevents.py riscv spacemit/x60 . pmu-events.c
```
It will fail, so remove the failing codes in jevents.py.
```
if len(archs) < 2:
  raise IOError(f'Missing architecture directory \'{_args.arch}\'')
```

Then, (I know some `make` options are not needed but it just works).

```
cd ..
make -j 8 V=1 VF=1 HOSTCC=riscv64-unknown-linux-gnu-gcc HOSTLD=riscv64-unknown-linux-gnu-ld CC=riscv64-unknown-linux-gnu-gcc CXX=riscv64-unknown-linux-gnu-g++ AR=riscv64-unknown-linux-gnu-ar LD=riscv64-unknown-linux-gnu-ld NM=riscv64-unknown-linux-gnu-nm PKG_CONFIG=riscv64-unknown-linux-gnu-pkg-config prefix=/usr bindir_relative=bin tipdir=share/doc/perf-6.8 'EXTRA_CFLAGS=-O2 -pipe' 'EXTRA_LDFLAGS=-Wl,-O1 -Wl,--as-needed' ARCH=riscv BUILD_BPF_SKEL= BUILD_NONDISTRO=1 JDIR= CORESIGHT= GTK2= feature-gtk2-infobar= NO_AUXTRACE= NO_BACKTRACE= NO_DEMANGLE= NO_JEVENTS=0
```

Cross compile fails, so just build it on your board. It took around 10mins on Milk-V jupiter.

## How to run ?

```
sudo perf record -e u_mode_cycle ls
sudo perf report # You might not need a root permission
```

There's other `-e` options you can try. Read lu\_zero's article [1].
```
perf record --group -e u_mode_cycle,m_mode_cycle,s_mode_cycle
perf record --group -e alu_inst,amo_inst,atomic_inst,fp_div_inst,fp_inst,fp_load_inst,fp_store_inst,load_inst,lr_inst,mult_inst,sc_inst,store_inst,unaligned_load_inst,unaligned_store_inst

```

## For dav1d?

I used this way:
```
sudo perf record -e u_mode_cycle dav1d -i sample_video.ivf -o /dev/null
```


See also:
 - https://dev.to/luzero/bringing-up-bpi-f3-part-25-27o4
