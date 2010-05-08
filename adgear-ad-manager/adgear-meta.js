(function($) {
  $(document).ready(function() {
    var dynamic = $("#adgear_site_is_dynamic").val() === "1";

    $("#adgear_adspot_id, #adgear_single").change(function(ev) {
      if (dynamic) return;

      var adspot = $("#adgear_adspot_id");
      var select = adspot.get()[0];
      var option = $(select.options[select.selectedIndex]);
      $("#adgear_embed_code").val("[adgear_ad id=" + adspot.val() + " name=\"" + option.text() + "\" single=" + $("#adgear_single").val() + "]");
    });

    $("#adgear_type, #adgear_slugify, #adgear_format_id, #adgear_path, #adgear_single").change(function(ev) {
      if (!dynamic) return;

      var slugify    = $("#adgear_slugify").val();
      var path       = $("#adgear_path").val();
      var format_id  = $("#adgear_format_id").val();
      var pathType   = $("#adgear_type").val();
      var single     = $("#adgear_single").val();
      var format     = $("#adgear_format_id").get()[0];
      var formatName = $(format.options[ format.selectedIndex ]).text();
      var pathParam;

      switch(pathType) {
        case 'categories':
          pathParam = "by_categories";
          break;

        case 'tags':
          pathParam = "by_tags";
          break;

        default:
          pathParam = '"' + path + '"';
          break;
      }

      $("#adgear_embed_code").val("[adgear_ad format=" + format_id + " name=\"" + formatName + "\" single=" + single + " slugify=" + slugify + " path=" + pathParam + "]");
    });

    $("#adgear_send_embed_code_to_editor").click(function(ev) {
      var embedCode = $("#adgear_embed_code").val();
      window.send_to_editor(embedCode);
      return false;
    });
  });
})(jQuery);
