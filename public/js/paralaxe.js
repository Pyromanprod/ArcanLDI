$(function () {

    //Création de la timeline
    const tl = gsap.timeline({
        scrollTrigger: {
            trigger: "#hero", //difinition de la div qui lancera l'animation
            start: "top top", //commence quand le "top" de la div touche le "top" de l'écran
            end: "bottom top",//fini quand le bottom de la div touche le top de l'ecran
            scrub: true, //scrub permet a l'animation de suivre le scroll de l'utilisateur
        }
    });

    //On définie quels élément du DOM vont etre animé
    gsap.utils.toArray(".parallax").forEach(layer => {
        const depth = layer.dataset.depth; //chaque "layer" a un data-depth dans le DOM qui définira la vitesse de déplacement
        const movement = -(layer.offsetHeight * depth)
        tl.to(layer, {y: movement, ease: "none"}, 0) //on lance l'animation de mouvement vertical a la vitesse défini au dessus (y: movement)
    });

    //oon selectionne l'image
    let img = $(".for-mobile .container img");
    gsap.to(img, {
//on prépare une animation smiplifié pour mobil
        alpha:0.5,
        scale:0,
        scrollTrigger: {
            trigger: img,
            start: 'top top',
            end: '40%',
            scrub: true,
        }
    });
    //idem mais pour le fond noir qui donnera un effet d'aspiration
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

