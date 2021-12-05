$(function () {


    $("#searchmember").on('input',function () {
        let search = $('.search')
        //input de la barre de recherche
        let input = search.val()
        //foreach sur les element avec la classe searching
        $('.searching').each(function (){
            //ajout de la classe visualy hidden a tout ce qui ne correspond pas a l'input de searchmember
            $(this).parent().parent().addClass('visually-hidden')
            //condition pour enlever la class visualy hidden au resultat correspondant a la recherche
            if ($(this).text().toLowerCase().includes(input.toLowerCase())){
                $(this).parent().parent().removeClass('visually-hidden')
            }
        })


    });
});
