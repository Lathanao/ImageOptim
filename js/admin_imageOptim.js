$(document).ready(function(){

    $('.generateImage').on('click', function(e){

        var url = $(this).data('href');

        $.post(
            url,
            function(data)
            {
                console.log(data);
            })
    });
});
