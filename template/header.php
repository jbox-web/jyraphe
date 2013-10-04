<!doctype html>
<html lang="fr">
  <head>
    <title><?php echo _('Jyraphe, your web file repository'); ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <link type="text/css" href="<?php echo $cfg['web_root'] . 'media/css/style.css'; ?>" rel="stylesheet" />

    <script type="text/javascript" src="<?php echo $cfg['web_root'] . 'media/javascript/jquery-1.10.2.min.js'; ?>"></script>

    <script type="text/javascript" src="<?php echo $cfg['web_root'] . 'media/javascript/file-upload/jquery.ui.widget.js'; ?>"></script>
    <script type="text/javascript" src="<?php echo $cfg['web_root'] . 'media/javascript/file-upload/jquery.fileupload.js'; ?>"></script>

    <script type="text/javascript">

      $(document).ready(function () {

        function fileSize(size) {
          var i = Math.floor( Math.log(size) / Math.log(1024) );
          return ( size / Math.pow(1024, i) ).toFixed(2) * 1 + ' ' + ['octets', 'Ko', 'Mo', 'Go', 'To'][i];
        }

        $.fn.reset = function () {
          $(this).each (function() { this.reset(); });
        }

        $('#file').fileupload({
          replaceFileInput: false,
          maxNumberOfFiles: 1,

          add: function (e, data) {
            var max_file_size = <?php echo hSystem::getMaxUploadSize(); ?>;
            var file_size_bit = data.files[0].size;
            var file_size     = fileSize(data.files[0].size);

            $('#progress .percentage').html('');
            $('#progress .bar').css('width', '0%');
            $('#upload_button').remove();
            $(".message").remove();

            $('#file_size').html('(' + file_size + ')');

            if(file_size_bit > max_file_size) {
              $('#messages').html('<div class="message"><p class="error">Filesize is too big</p></div>');
            } else {
              $('#jyraphe_moreoptions').show();

              data.context = $('<button/>').text('Upload')
                                .attr({id: 'upload_button'})
                                .appendTo($('#upload_fieldset'))
                                .click(function () {
                                  data.context = $('<p/>')
                                    .attr({id: 'upload_message'})
                                    .text('Uploading...').replaceAll($(this));
                                  data.submit();
                                });
            }
          },

          fail: function (e, data) {
            $('#jyraphe_moreoptions').hide();
            $('#upload_form').reset();

            $('#upload_message').remove();
            $('#messages').html(data.result);
          },

          done: function (e, data) {
            $('#jyraphe_moreoptions').hide();
            $('#upload_form').reset();

            $('#upload_message').remove();
            $('#messages').html(data.result);
          },

          progressall: function (e, data) {
            var progress = parseInt(data.loaded / data.total * 100, 10);
            $('#progress .percentage').html(progress + '%');
            $('#progress .bar').css('width', progress + '%');
          }
        });

      });
    </script>
  </head>

  <body>

    <div id="content">

      <h1><a href="<?php echo $cfg['web_root']; ?>"><?php echo _('JBox Web'); ?></a></h1>
      <h1><a href="<?php echo $cfg['web_root']; ?>"><?php echo _('File repository'); ?></a></h1>
      <br />
