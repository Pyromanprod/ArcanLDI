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

//animation des jeu sur page accueil
    let tween = gsap.from(".thegame", {
        autoAlpha: 0,
        scrollTrigger: {
            trigger: ".thegame",
            start: "20% bottom",
            end: '+=40%',
            // markers: true,
            scrub: true,
        }
    })

});

