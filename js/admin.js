jQuery(document).ready(function ($) {
    // console.log('ajaxuxl: ' + admin.ajaxurl, admin.nonce);

    $('.mail-action').on('click', function () {
        if (!confirm('ステータスを変更してもよろしいですか？')) {
            return false;
        }

        let mail_id = $(this).closest('tr').data('id'); // 親要素を遡って、最も近いtr要素
        let status = $(this).hasClass('stopped') ? 1 : 0;
        let element = $(this);

        console.log(mail_id, status);

        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: admin.ajaxurl,
            data: {
                action: 'change_subscribe_status',
                mail_id: mail_id,
                status: status,
                nonce: admin.nonce
            }
        })
            .done(function (response, textStatus, jqXHR) {
                if (response.success) {
                    console.log(response.data);
                    element.toggleClass('stopped');
                    let label = element.text() === '有効' ? '停止' : '有効';
                    element.text(label);
                } else {
                    console.log(response.data);
                }
            })
            .fail(function (jqXHR, textStatus, error) {
                console.error('Status:', textStatus, error);
                console.error('Error:', jqXHR.responseText);
            });
    });
});
