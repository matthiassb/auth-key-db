// Options for Message
//----------------------------------------------
var options = {
  'btn-loading': '<i class="fa fa-spinner fa-pulse"></i>',
  'btn-success': '<i class="fa fa-check"></i>',
  'btn-error': '<i class="fa fa-remove"></i>',
  'msg-success': 'All Good! Redirecting...',
  'msg-error': 'Wrong login credentials!'
};

$(document).ready(function() {


  // Login Form
  //----------------------------------------------
  // Validation
  $("#login-form").validate({
    rules: {
      username: "required",
      password: "required",
    },
    errorClass: "form-invalid"
  });

  // Form Submission
  $('#login-form').on('submit', function(e) {
    e.preventDefault();
    form_loading($(this));
    $.post(window.location.pathname, $(this).serialize(), function(response) {
      console.log(response);
      if (response.status == "success") {
        form_success($('#login-form'));
        window.setTimeout(function() {
          window.location.reload()
        }, 2000)
      } else {
        form_failed($('#login-form'));
      }
      remove_loading($(this));

    }, 'json');


  });

  $('#addKey').click(function(e) {
    $this = $('#addKey');
    swal({
      title: "Add Key",
      text: "Add your ssh public key below:",
      type: "input",
      showCancelButton: true,
      closeOnConfirm: false,
      animation: "slide-from-top",
    }, function(inputValue) {
      if (inputValue === false) return false;
      if (inputValue === "") {
        swal.showInputError("You need to input your key");
        return false
      }
      $.post(window.location.pathname, {
        "username": $this.data('username'),
        "key": inputValue,
        "action": "add-key"
      }, function(response) {
        swal({type: response.status, text: response.message, title: ''})
        if(response.status == "success"){
          window.setTimeout(function(){
            window.location.reload();
          }, 1500)
        }
      });
    });

  });

  $(".viewKey").click(function() {
    swal({
      title: "View Key",
      text: "<textarea id='editKey' style='height: 250px; width: 100%;'>" + $(this).data("key") + "</textarea>",
      html: true
    });
  })

  $(".deleteKey").click(function() {
    $this = $(this);
    $.post(window.location.pathname, {
      "key": $this.data('key'),
      "action": "delete-key",
      "username": $this.data('username'),
    }, function(response) {
      swal({type: response.status, text: response.message, title: ''})
      if(response.status == "success"){
        window.setTimeout(function(){
          window.location.reload();
        }, 1500)
      }
    });
  })
  // Loading
  //----------------------------------------------
  function remove_loading($form) {
    $form.find('[type=submit]').removeClass('error success');
    $form.find('.login-form-main-message').removeClass('show error success').html('');
  }

  function form_loading($form) {
    $form.find('[type=submit]').addClass('clicked').html(options['btn-loading']);
  }

  function form_success($form, message) {
    $form.find('[type=submit]').addClass('success').html(options['btn-success']);
    $form.find('.login-form-main-message').removeClass('error')
    $form.find('.login-form-main-message').addClass('show success').html(options['msg-success']);
  }

  function form_failed($form, message) {
    $form.find('[type=submit]').addClass('error').html(options['btn-error']);
    $form.find('.login-form-main-message').remove('success')
    $form.find('.login-form-main-message').addClass('show error').html(options['msg-error']);
  }

  // Dummy Submit Form (Remove this)
  //----------------------------------------------
  // This is just a dummy form submission. You should use your AJAX function or remove this function if you are not using AJAX.
  function dummy_submit_form($form) {
    if ($form.valid()) {
      form_loading($form);

      setTimeout(function() {
        form_success($form);
      }, 2000);
    }
  }
});
