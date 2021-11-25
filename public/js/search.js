$(function () {


    $("#searchmember").on('input',function () {


        let search = $('.search')
        let input = search.val()
        $('.searching').each(function (){
            $(this).parent().addClass('visually-hidden')
            if ($(this).text().toLowerCase().includes(input)){
                $(this).parent().removeClass('visually-hidden')
            }
        })


    });

});
