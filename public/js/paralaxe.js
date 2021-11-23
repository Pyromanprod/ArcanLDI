$(function () {


    const tl = gsap.timeline({
        scrollTrigger: {
            trigger: "#hero",
            start: "top top",
            end: "bottom top",
            scrub: true,
        }
    });

    gsap.utils.toArray(".parallax").forEach(layer => {
        const depth = layer.dataset.depth;
        const movement = -(layer.offsetHeight * depth)
        tl.to(layer, {y: movement, ease: "none"}, 0)
    });

    let img = $(".for-mobile .container img");
    gsap.to(img, {

        alpha:0.5,
        scale:0,
        scrollTrigger: {
            trigger: img,
            start: 'top top',
            end: '40%',
            scrub: true,
        }
    });
    let fond = $(".for-mobile .container");
    gsap.to(fond, {

        alpha:0,
        scale:0,
        scrollTrigger: {
            trigger: fond,
            start: 'top top',
            end: 'bottom top',
            scrub: true,
        }
    });
});

