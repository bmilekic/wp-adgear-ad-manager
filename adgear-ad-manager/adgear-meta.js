function adgearStaticSiteChange($, root) {
  var root = $(root);

  var adspot = root.find(".adgear_adspot_selector");
  var select = adspot.get()[0];
  var option = $(select.options[select.selectedIndex]);

  var sendCode = $("#adgear_send_embed_code_to_editor");
  if (sendCode.length === 0) sendCode = null;

  var value, css;
  if (adspot.val() === "") {
    value = "Choose your ad spot first";
    css   = {"color": "#c33", "font-style": "italic"};

    if (sendCode) sendCode.css({opacity: 0.5}).get()[0].disabled = true;
  } else {
    value = "[adgear_ad id=" + adspot.val() + " name=\"" + option.text() + "\" single=" + root.find(".adgear_single_selector").val() + "]"
    css   = {"color": "black", "font-style": "normal"};

    if (sendCode) sendCode.css({opacity: 1.0}).get()[0].disabled = false;
  }

  $("#adgear_embed_code").val(value).css(css);
}

function adgearDynamicSiteChange($, root) {
  var root = $(root);

  var slugify    = root.find(".adgear_slugify_selector").val();
  var path       = root.find(".adgear_path").val();
  var format_id  = root.find(".adgear_format_selector").val();
  var pathPre    = root.find(".adgear_path_pre").val();
  var pathMiddle = root.find(".adgear_path_middle").val();
  var pathPost   = root.find(".adgear_path_post").val();
  var pathType   = root.find(".adgear_path_type_selector").val();
  var single     = root.find(".adgear_single_selector").val();
  var format     = root.find(".adgear_format_selector").get()[0];
  var formatName = $(format.options[ format.selectedIndex ]).text();
  var pathParam;

  switch(pathType) {
    case 'categories':
    if (! root.find(".adgear_toggle_advanced")[0].checked) {
      root.find(".adgear_example").hide();
    }
    root.find(".adgear_path").hide();
    pathParam = "by_categories";
    break;

    case 'tags':
    if (! root.find(".adgear_toggle_advanced")[0].checked) {
      root.find(".adgear_example").hide();
    }
    root.find(".adgear_path").hide();
    pathParam = "by_tags";
    break;

    default:
    root.find(".adgear_example").show();
    root.find(".adgear_path").show();
    pathParam = '"' + path + '"';
    break;
  }

  var sendCode = $("#adgear_send_embed_code_to_editor");
  if (sendCode.length === 0) sendCode = null;

  var css, value;
  if (format_id === "") {
    value = "Choose your ad format first";
    css   = {"color": "#c33", "font-style": "italic"};

    if (sendCode) sendCode.css({opacity: 0.5}).get()[0].disabled = true;
  } else {
    var tagCode = {format      : format_id,
                   name        : formatName,
                   single      : single,
                   slugify     : slugify,
                   path        : pathParam,
                   path_pre    : pathPre,
                   path_middle : pathMiddle,
                   path_post   : pathPost};

    value = "[adgear_ad";
    for(var key in tagCode) {
      value += " " + key + '="' + tagCode[key] + '"';
    }
    value += "]";

    css   = {"color": "black", "font-style": "normal"};

    if (sendCode) sendCode.css({opacity: 1.0}).get()[0].disabled = false;
  }

  $("#adgear_embed_code").val(value).css(css);
}

(function($) {
  $(document).ready(function() {
    var dynamic = $("#adgear_site_is_dynamic").val() === "1";

    $(".adgear-meta .adgear_adspot_selector, .adgear-meta .adgear_single_selector").live("change", function(ev) {
      if (dynamic) return;
      adgearStaticSiteChange($, $(ev.target).parents('.adgear-meta'));
    });

    $(".adgear-meta .adgear_path_type_selector, .adgear-meta .adgear_slugify_selector, .adgear-meta .adgear_format_selector, .adgear-meta .adgear_path, .adgear-meta .adgear_single_selector, .adgear-meta .adgear_path_pre, .adgear-meta .adgear_path_middle, .adgear-meta .adgear_path_post").live("change", function(ev) {
      if (!dynamic) return;
      adgearDynamicSiteChange($, $(ev.target).parents('.adgear-meta'));
    });

    $("#adgear_send_embed_code_to_editor").click(function(ev) {
      var embedCode = $("#adgear_embed_code").val();
      window.send_to_editor(embedCode);
      return false;
    });

    $(".adgear_toggle_advanced").live("change", function() {
      var root = $(this).closest(".adgear-meta");
      var rows = root.find(".adgear_advanced");
      if ($(this)[0].checked) {
        rows.show();
        root.find(".adgear_example").show();
      } else {
        rows.hide();
        rows.find("input").val("");
        if (root.find(".adgear_path_type_selector").val() !== "path") {
          root.find(".adgear_example").hide();
        }
      }
    });
  });
})(jQuery);
