(function($) {
  $(document).ready(function() {
    $("#adgear_adspot_id").change(function(ev) {
      var select = $(ev.currentTarget);
      var option = $(ev.currentTarget.options[ev.currentTarget.selectedIndex]);
      $("#adgear_embed_code").val("[adgear_ad format=" + select.val() + " name=\"" + option.text() + "\"]");
    });

    $("#adgear_send_embed_code_to_editor").click(function(ev) {
      var embedCode = $("#adgear_embed_code").val();
      window.send_to_editor(embedCode);
      return false;
    });
  });
})(jQuery);
