$(function () {


    $("#searchmember").on('input',function () {
        let search = $('.search')
        let input = search.val()
        $('.searching').each(function (){
            $(this).parent().parent().addClass('visually-hidden')
            if ($(this).text().toLowerCase().includes(input.toLowerCase())){
                $(this).parent().parent().removeClass('visually-hidden')
            }
        })


    });

});
