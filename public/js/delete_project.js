//Initialize dialog
$( document ).ready(function() {


    $("#opener").click(function () {
        $("#dialog").modal("show");
    });

    $("#delete").click(function (e){
        e.preventDefault();

        let projectId = $(this).val();
        $.ajax({
            url: '/project/delete/project',
            data: {'projectId': projectId},
            method: 'post',
            success: function ()
            {
                location.href = "/"
            },
            error: function ()
            {
                $("#dialog").modal("hide");
                alert("Coś poszło nie tak");
            }
        });
    })
});