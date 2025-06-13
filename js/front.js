jQuery(document).ready(function ($) {
    console.log('ajaxurl: ' + front.ajaxurl);

    $('.register_email').on('click', function (e) {
        const email = $('input[name="email"]').val();

        if (!isValidEmail(email)) {
            alert('メールアドレスの形式が正しくありません。');
            return false;
        }

        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: front.ajaxurl,
            data: {
                action: 'subscribe_email',
                email: email,
                nonce: front.nonce
            }
        })
            .done(function (response, textStatus, jqXHR) {
                if (response.success) {
                    console.log(response.data.message);
                    $('.my-subscribe-message').text(response.data.message);
                    $('.my-subscribe-message').removeClass('error');
                    $('.my-subscribe-message').fadeIn();
                } else {
                    $('.my-subscribe-message').text(response.data.message);
                    $('.my-subscribe-message').addClass('error');
                    $('.my-subscribe-message').fadeIn();
                    console.error('Status:', textStatus);
                    console.error(response.data.message);
                }
            })
            .fail(function (jqXHR, textStatus, error) {
                console.error('Status:', textStatus, error);
                console.error('Error:', jqXHR.responseText);
            });
    });

    $('.unsubscribe_email').on('click', function (e) {
        let email = $('input[name="email"]').val();

        if (!isValidEmail(email)) {
            alert('メールアドレスの形式が正しくありません。');
            return false;
        }

        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: front.ajaxurl,
            data: {
                action: 'unsubscribe_email',
                email: email,
                nonce: front.nonce
            }
        })
            .done(function (response, textStatus, jqXHR) {
                if (response.success) {
                    console.log(response.data.message);
                    $('.my-subscribe-message').text(response.data.message);
                    $('.my-subscribe-message').removeClass('error');
                    $('.my-subscribe-message').fadeIn();
                } else {
                    $('.my-subscribe-message').text(response.data.message);
                    $('.my-subscribe-message').addClass('error');
                    $('.my-subscribe-message').fadeIn();
                    console.error('Status:', textStatus);
                    console.error(response.data.message);
                }
            })
            .fail(function (jqXHR, textStatus, error) {
                console.error('Status:', textStatus, error);
                console.error('Error:', jqXHR.responseText);
            });
    });

    function isValidEmail(email) {
        // 一般的なメールアドレスの正規表現
        const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return pattern.test(email);
    }
});
