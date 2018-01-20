$(document).ready(function(){

    $('.generateImage').on('click', function(e){

        $button = $(this);
        $button.html('<img src="../modules/ImageOptim/img/loader.gif" style="height: 14px; width: 14px; margin-right: 5px;">Working...');

        $tr = $(this).closest("tr");

        var url = $(this).data('href');

        $.post(url).then(function (resp) {

            if(resp.error){

                $button.html('<i class="icon-exclamation-sign" style="margin-right: 5px; color:red"></i>Fail');
                $.growl.error({ message: "An error occurred in core module: "+resp.error });

            } else {

                var weigthbefore = $tr.find('td:nth-child(5)').text() * 1;
                var rate = Math.round((parseInt(resp.imageopt.weight_opt) / parseInt(weigthbefore) * 100) * 100) / 100 ;
                $tr.find('td:nth-child(6)').replaceWith('<td class="" style="background: palegreen;">'+resp.imageopt.weight_opt+'</td>');
                $tr.find('td:nth-child(7)').replaceWith('<td class="" style="background: palegreen;">'+rate+'</td>');

                $button.html('<i class="icon-check" style="margin-right: 5px; color:green"></i>Done');
                $.growl.notice({ message: "Image successful optimized." });

            }
        }).fail(function (resp) {
            $button.html('<i class="icon-exclamation-sign" style="margin-right: 5px; color:red"></i>Fail');
            $.growl.error({ message: "An error occurred during server connection." });
        });
    });
});
