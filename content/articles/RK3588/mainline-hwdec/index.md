---
title: "Hardware-accelerated 4K video on RK3588 with Linux 7.0"
date: 2026-04-20
toc: true
tags: [Linux, FFmpeg, mpv, GStreamer, RK3588, Collabora]
---

## Background

Collabora team has been doing amazing work. Now you don't need to use vendor BSP to use hardware acceleration.
Now with upstream linux kernel 7.0, you can play 4K 60fps video with hardware acceleration without any vendor kernel patch.

First of all, thank you so much to my friend for lending me Odroid-M2[^odroid] in this RAMpocalypse.

## But how to do it?

I couldn't find a guide on how to actually run it on an upstream kernel.
That's why I wrote this article.

I could get some hints from Collabora's blog post[^collabora].
And this Github discussion[^github] was helpful too.

### This is what you need

- Upstream Linux kernel 7.0+
- Enable `CONFIG_VIDEO_ROCKCHIP_VDEC=m` in the default kernel config.
- linux-firmware (It's for Panthor GPU driver, not for VPU)
- gstreamer 1.28+
- sway or any wayland compositor that supports dmabuf for zero copy (you may be able to use DRM or X11, but not tested)

> [!NOTE]
> Some of these aren't needed if you just want `decoding`. It's done by VPU, so you might only need the upstream kernel. I'm focusing on **playing** video, which requires integration with GPU. And GPU needs some configurations.

These were enabled by default for me, but you might need to enable them yourself:

- `CONFIG_MEDIA_CONTROLLER=y`
- `CONFIG_MEDIA_CONTROLLER_REQUEST_API=y`
- `CONFIG_V4L2_MEM2MEM_DEV=y`
- `CONFIG_DRM_PANTHOR=m`

Or you can use ffmpeg/mpv instead of gstreamer. These patches add v4l2request support:

- ffmpeg with this patch[^ffmpeg] (or you can use this fork[^fork])
- mpv with this patch[^mpv]

I believe you can do 'hardware decoding' without any patch with ffmpeg even on TTY. But you may be able to use it only for transcoding or whatever you need it for, but not for playing video AFAIK.

### How to play it

```sh
v4l2-ctl -d /dev/videoN --list-formats-out
v4l2-ctl -d /dev/videoM --list-formats-out
```

With patched ffmpeg + mpv on wayland compositor that supports linux-dmabuf-v1:

```sh
mpv --hwdec=v4l2request --vo=dmabuf-wayland --hwdec-software-fallback=no bbb_sunflower_2160p_60fps_normal.mp4
```

`--hwdec-software-fallback=no` isn't necessary but it's useful for not making it fallback to software decoding.

> But how to get board image?

## crossdev-stages

You can build image with [crossdev-stages](https://github.com/lu-zero/crossdev-stages).
Making an image for RISC-V/ARM boards is usually not that convenient.
It removes all the headache when you build an image for boards. It makes a container with `hakoniwa` and sets up a cross compiler environment with Gentoo Linux.
Then you can build rootfs + linux + uboot + whatever automatically, designed so everything can be defined with config files.

I added multi-board support on top of Luca's original work. It used to only support RISC-V boards, now you can use ARM boards too.

## Voilà!

{{< video "demo.webm" >}}

## Upcoming patches?

There are more works waiting for merge, like 4K 60fps HDMI output. This is really amazing work. You can check details in the resources at the bottom.

## Mini VideoBox

I don't want this to just be playing with my Odroid-M2, I want to make it useful.

For future plan, I'm trying to make a 'Mini-VideoBox' someday. FOSDEM has a streaming device called `videobox`[^videobox], it's an amazing thing. I had a great chance to use and analyze it, and they also donated one to us, [FOSS for All](https://fossforall.org).

But it's bit big and it's for huge conferences. I'm more trying to make a streaming+recording device that can work standalone. Since RK3588 has HDMI input (Unfortunately Odroid-M2 uses RK3588S2 which has no HDMI input), this could be a perfect fit.

My plan is to build several mini-videoboxes for small meetups. I'd love to do this as a FOSS for All project, building it openly with the community. So people can record their talk and meetup painlessly. No OBS on their laptop, no capture card.

We need more time before all features land in the upstream kernel, but I believe it can be done someday.

## Thank you!

Kudos to the Collabora team!
And again, thanks to my friend `C` who lent me his Odroid-M2.

## Other useful resources

- [Mainline status of RK3588](https://gitlab.collabora.com/hardware-enablement/rockchip-3588/notes-for-rockchip-3588/-/blob/main/mainline-status.md)
- [No Line Like Mainline: Update On The Fully Mainline Software Stack For Rockchip SoCs](https://fosdem.org/2026/schedule/event/KLFW73-no-line-like-mainline-rockchip/)
- [Upstreaming Progress: Video Capture and Camera Support for Recent Rockchip SoCs](https://fosdem.org/2026/schedule/event/WFZJ7U-upstream-video-capture-camera-support-rk35xy/)
- [Odroid-M2 wiki](https://wiki.odroid.com/odroid-m2/odroid-m2)
- [After FOSDEM VideoBox Updates (BrixIT blog)](https://blog.brixit.nl/after-fosdem-videobox-updates/)
- [FOSDEM VideoBox 2026 (talk)](https://fosdem.org/2026/schedule/event/ULTMMY-fosdem_videobox_2026/)
- [FOSDEM video tooling source](https://github.com/fosdem/video)

[^odroid]: [Odroid-M2 wiki](https://wiki.odroid.com/odroid-m2/odroid-m2)

[^collabora]: [Collabora's article about merged patch](https://www.collabora.com/news-and-blog/news-and-events/rk3588-and-rk3576-video-decoders-support-merged-in-the-upstream-linux-kernel.html)

[^github]: [Github discussion about HW acceleration for RK3588 with mainline kernel](https://github.com/blakeblackshear/frigate/discussions/18311)

[^ffmpeg]: [FFmpeg PR](https://code.ffmpeg.org/FFmpeg/FFmpeg/pulls/20847)

[^fork]: [FFmpeg downstream of collabora](https://gitlab.collabora.com/detlev/ffmpeg)

[^mpv]: [mpv PR](https://github.com/mpv-player/mpv/pull/14690)

[^videobox]: [After FOSDEM VideoBox Updates (BrixIT blog)](https://blog.brixit.nl/after-fosdem-videobox-updates/)
