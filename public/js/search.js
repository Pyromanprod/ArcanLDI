$(function () {


    $("#searchmember").on('input',function () {


        let search = $('.search')
        let input = search.val()
        $('.form-check-label').each(function (){
            $(this).parent().addClass('visually-hidden')
            if ($(this).text().includes(input)){
                $(this).parent().removeClass('visually-hidden')
                console.log('pwet')
            }
        })


    });

});
