// ---------------------------------------------------------------- Start Language Change --------------------------------------------------------------
$('.language-change').on('change', function (e) {
    e.preventDefault();
    var url = $('#url').val();
    var code = $(this).val();

    var formData = {
        code: code,
    }
    $.ajax({
        type: "GET",
        dataType: 'html',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: url + '/languages/change',
        success: function (data) {
            if (data == 1) {
                location.reload();
            } else {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer)
                        toast.addEventListener('mouseleave', Swal.resumeTimer)
                    }
                })

                Toast.fire({
                    icon: 'error',
                    title: 'Language terms not generate yet!'
                })
                location.reload();
            }
        },
        error: function (data) {
        }
    });
});
// ---------------------------------------------------------------- End Language Change ----------------------------------------------------------------
