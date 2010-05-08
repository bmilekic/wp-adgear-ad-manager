function adgearStaticSiteChange($) {
  var adspot = $("#adgear_adspot_id");
  var select = adspot.get()[0];
  var option = $(select.options[select.selectedIndex]);

  var value, css;
  if (adspot.val() === "") {
    value = "Choose your ad spot first";
    css   = {"color": "#c33", "font-style": "italic"};
    $("#adgear_send_embed_code_to_editor").css({opacity: 0.5}).get()[0].disabled = true;
  } else {
    value = "[adgear_ad id=" + adspot.val() + " name=\"" + option.text() + "\" single=" + $("#adgear_single").val() + "]"
    css   = {"color": "black", "font-style": "normal"};
    $("#adgear_send_embed_code_to_editor").css({opacity: 1.0}).get()[0].disabled = false;
  }

  $("#adgear_embed_code").val(value).css(css);
}

function adgearDynamicSiteChange($) {
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
    $("#adgear_path").hide();
    pathParam = "by_categories";
    break;

    case 'tags':
    $("#adgear_path").hide();
    pathParam = "by_tags";
    break;

    default:
    $("#adgear_path").show();
    pathParam = '"' + path + '"';
    break;
  }

  var css, value;
  if (format_id === "") {
    value = "Choose your ad format first";
    css   = {"color": "#c33", "font-style": "italic"};
    $("#adgear_send_embed_code_to_editor").css({opacity: 0.5}).get()[0].disabled = true;
  } else {
    value = "[adgear_ad format=" + format_id + " name=\"" + formatName + "\" single=" + single + " slugify=" + slugify + " path=" + pathParam + "]";
    css   = {"color": "black", "font-style": "normal"};
    $("#adgear_send_embed_code_to_editor").css({opacity: 1.0}).get()[0].disabled = false;
  }

  $("#adgear_embed_code").val(value).css(css);
}

(function($) {
  $(document).ready(function() {
    var dynamic = $("#adgear_site_is_dynamic").val() === "1";

    $("#adgear_adspot_id, #adgear_single").change(function(ev) {
      if (dynamic) return;
      adgearStaticSiteChange($);
    });

    $("#adgear_type, #adgear_slugify, #adgear_format_id, #adgear_path, #adgear_single").change(function(ev) {
      if (!dynamic) return;
      adgearDynamicSiteChange($);
    });

    $("#adgear_send_embed_code_to_editor").click(function(ev) {
      var embedCode = $("#adgear_embed_code").val();
      window.send_to_editor(embedCode);
      return false;
    });
  });
})(jQuery);
