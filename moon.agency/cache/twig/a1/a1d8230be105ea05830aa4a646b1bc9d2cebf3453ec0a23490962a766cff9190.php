<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* forms/default/form.html.twig */
class __TwigTemplate_ac51e466496834c95be50fa07233a7a0065638ab39bd8898c3d728a818f2f63a extends \Twig\Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
            'form_classes' => [$this, 'block_form_classes'],
            'inner_markup_fields_start' => [$this, 'block_inner_markup_fields_start'],
            'inner_markup_fields_end' => [$this, 'block_inner_markup_fields_end'],
            'inner_markup_fields' => [$this, 'block_inner_markup_fields'],
            'inner_markup_field_open' => [$this, 'block_inner_markup_field_open'],
            'field' => [$this, 'block_field'],
            'inner_markup_field_close' => [$this, 'block_inner_markup_field_close'],
            'inner_markup_buttons_start' => [$this, 'block_inner_markup_buttons_start'],
            'inner_markup_buttons_end' => [$this, 'block_inner_markup_buttons_end'],
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        // line 1
        $context["form"] = (($context["form"]) ?? ($this->getAttribute($this->getAttribute(($context["grav"] ?? null), "session", []), "getFlashObject", [0 => "form"], "method")));
        // line 2
        $context["layout"] = (($context["layout"]) ?? (((($this->getAttribute(($context["form"] ?? null), "layout", [], "any", true, true) &&  !(null === $this->getAttribute(($context["form"] ?? null), "layout", [])))) ? ($this->getAttribute(($context["form"] ?? null), "layout", [])) : ("default"))));
        // line 3
        $context["field_layout"] = (($context["field_layout"]) ?? (($context["layout"] ?? null)));
        // line 4
        echo "
";
        // line 6
        $this->loadTemplate("partials/form-messages.html.twig", "forms/default/form.html.twig", 6)->display($context);
        // line 7
        echo "
";
        // line 8
        $context["scope"] = ((($context["scope"] ?? null)) ? (($context["scope"] ?? null)) : ((($this->getAttribute(($context["form"] ?? null), "scope", [], "any", true, true)) ? ($this->getAttribute(($context["form"] ?? null), "scope", [])) : ("data."))));
        // line 9
        $context["multipart"] = "";
        // line 10
        $context["blueprints"] = (($context["blueprints"]) ?? ($this->getAttribute(($context["form"] ?? null), "blueprint", [], "method")));
        // line 11
        $context["method"] = _twig_default_filter(twig_upper_filter($this->env, $this->getAttribute(($context["form"] ?? null), "method", [])), "POST");
        // line 12
        $context["client_side_validation"] = (( !(null === $this->getAttribute(($context["form"] ?? null), "client_side_validation", []))) ? ($this->getAttribute(($context["form"] ?? null), "client_side_validation", [])) : ($this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->definedDefaultFilter($this->getAttribute($this->getAttribute($this->getAttribute(($context["config"] ?? null), "plugins", []), "form", []), "client_side_validation", []), true)));
        // line 13
        $context["inline_errors"] = (( !(null === $this->getAttribute(($context["form"] ?? null), "inline_errors", []))) ? ($this->getAttribute(($context["form"] ?? null), "inline_errors", [])) : ($this->getAttribute($this->getAttribute($this->getAttribute(($context["config"] ?? null), "plugins", []), "form", []), "inline_errors", [0 => false], "method")));
        // line 14
        echo "
";
        // line 15
        $context["data"] = (($context["data"]) ?? ($this->getAttribute(($context["form"] ?? null), "data", [])));
        // line 16
        $context["context"] = (($context["context"]) ?? (($context["data"] ?? null)));
        // line 17
        echo "
";
        // line 18
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable($this->getAttribute(($context["form"] ?? null), "fields", []));
        foreach ($context['_seq'] as $context["_key"] => $context["field"]) {
            // line 19
            echo "    ";
            if (((($context["method"] ?? null) == "POST") && ($this->getAttribute($context["field"], "type", []) == "file"))) {
                // line 20
                echo "        ";
                $context["multipart"] = " enctype=\"multipart/form-data\"";
                // line 21
                echo "    ";
            }
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['field'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 23
        echo "
";
        // line 24
        $context["action"] = (($context["action"]) ?? ((($this->getAttribute(($context["form"] ?? null), "action", [])) ? ($this->getAttribute(($context["form"] ?? null), "action", [])) : (($this->getAttribute(($context["page"] ?? null), "route", []) . $this->getAttribute(($context["uri"] ?? null), "params", []))))));
        // line 25
        $context["action"] = ((((is_string($__internal_f607aeef2c31a95a7bf963452dff024ffaeb6aafbe4603f9ca3bec57be8633f4 = ($context["action"] ?? null)) && is_string($__internal_62824350bc4502ee19dbc2e99fc6bdd3bd90e7d8dd6e72f42c35efd048542144 = "http") && ('' === $__internal_62824350bc4502ee19dbc2e99fc6bdd3bd90e7d8dd6e72f42c35efd048542144 || 0 === strpos($__internal_f607aeef2c31a95a7bf963452dff024ffaeb6aafbe4603f9ca3bec57be8633f4, $__internal_62824350bc4502ee19dbc2e99fc6bdd3bd90e7d8dd6e72f42c35efd048542144))) || (is_string($__internal_1cfccaec8dd2e8578ccb026fbe7f2e7e29ac2ed5deb976639c5fc99a6ea8583b = ($context["action"] ?? null)) && is_string($__internal_68aa442c1d43d3410ea8f958ba9090f3eaa9a76f8de8fc9be4d6c7389ba28002 = "#") && ('' === $__internal_68aa442c1d43d3410ea8f958ba9090f3eaa9a76f8de8fc9be4d6c7389ba28002 || 0 === strpos($__internal_1cfccaec8dd2e8578ccb026fbe7f2e7e29ac2ed5deb976639c5fc99a6ea8583b, $__internal_68aa442c1d43d3410ea8f958ba9090f3eaa9a76f8de8fc9be4d6c7389ba28002))))) ? (($context["action"] ?? null)) : ((($context["base_url"] ?? null) . ($context["action"] ?? null))));
        // line 26
        $context["action"] = twig_trim_filter(($context["action"] ?? null), "/", "right");
        // line 27
        echo "
";
        // line 28
        if ((($context["action"] ?? null) == ($context["base_url_relative"] ?? null))) {
            // line 29
            echo "    ";
            $context["action"] = (($context["base_url_relative"] ?? null) . "/");
        }
        // line 31
        echo "
";
        // line 32
        if ($this->getAttribute(($context["form"] ?? null), "keep_alive", [])) {
            // line 33
            echo "    ";
            if ((($this->getAttribute($this->getAttribute(($context["grav"] ?? null), "browser", []), "browser", []) == "msie") && ($this->getAttribute($this->getAttribute(($context["grav"] ?? null), "browser", []), "version", []) < 12))) {
                // line 34
                echo "        ";
                $this->getAttribute(($context["assets"] ?? null), "addJs", [0 => "plugin://form/assets/object.assign.polyfill.js"], "method");
                // line 35
                echo "    ";
            }
            // line 36
            echo "    ";
            $this->getAttribute(($context["assets"] ?? null), "addJs", [0 => "plugin://form/assets/form.vendor.js", 1 => ["group" => "bottom", "loading" => "defer"]], "method");
            // line 37
            echo "    ";
            $this->getAttribute(($context["assets"] ?? null), "addJs", [0 => "plugin://form/assets/form.min.js", 1 => ["group" => "bottom", "loading" => "defer"]], "method");
        }
        // line 39
        echo "
";
        // line 40
        $this->getAttribute(($context["assets"] ?? null), "addInlineJs", [0 => (((((((((((("
    window.GravForm = window.GravForm || {};
    window.GravForm.config = {
        current_url: '" . $this->getAttribute($this->getAttribute(        // line 43
($context["grav"] ?? null), "route", []), "withoutParams", [], "method")) . "',
        current_params: ") . twig_jsonencode_filter($this->getAttribute($this->getAttribute(        // line 44
($context["grav"] ?? null), "route", []), "params", []))) . ",
        param_sep: '") . $this->getAttribute($this->getAttribute(        // line 45
($context["config"] ?? null), "system", []), "param_sep", [])) . "',
        base_url_relative: '") .         // line 46
($context["base_url_relative"] ?? null)) . "',
        form_nonce: '") . $this->getAttribute(        // line 47
($context["form"] ?? null), "getNonce", [], "method")) . "',
        session_timeout: ") . $this->getAttribute($this->getAttribute($this->getAttribute(        // line 48
($context["config"] ?? null), "system", []), "session", []), "timeout", [])) . "
    };
    window.GravForm.translations = Object.assign({}, window.GravForm.translations || {}, { PLUGIN_FORM: {} });
"), 1 => ["group" => "bottom", "position" => "before", "priority" => 100]], "method");
        // line 52
        echo "
";
        // line 54
        ob_start();
        // line 55
        echo "  ";
        $this->displayBlock('form_classes', $context, $blocks);
        $context["override_form_classes"] = ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
        // line 59
        echo "
";
        // line 60
        ob_start();
        // line 61
        echo "  ";
        $this->displayBlock('inner_markup_fields_start', $context, $blocks);
        $context["override_inner_markup_fields_start"] = ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
        // line 63
        echo "
";
        // line 64
        ob_start();
        // line 65
        echo "  ";
        $this->displayBlock('inner_markup_fields_end', $context, $blocks);
        $context["override_inner_markup_fields_end"] = ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
        // line 67
        echo "
";
        // line 68
        ob_start();
        // line 69
        echo "  ";
        $this->displayBlock('inner_markup_fields', $context, $blocks);
        $context["override_inner_markup_fields"] = ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
        // line 85
        echo "
";
        // line 86
        ob_start();
        // line 87
        echo "  ";
        $this->displayBlock('inner_markup_buttons_start', $context, $blocks);
        $context["override_inner_markup_buttons_start"] = ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
        // line 91
        echo "
";
        // line 92
        ob_start();
        // line 93
        echo "  ";
        $this->displayBlock('inner_markup_buttons_end', $context, $blocks);
        $context["override_inner_markup_buttons_end"] = ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
        // line 97
        echo "
";
        // line 99
        $this->loadTemplate("forms/default/form.html.twig", "forms/default/form.html.twig", 99, "1294676812")->display($context);
        // line 202
        echo "

";
        // line 204
        if ($this->getAttribute($this->getAttribute($this->getAttribute(($context["config"] ?? null), "forms", []), "dropzone", []), "enabled", [])) {
            // line 205
            echo "<div id=\"dropzone-template\" style=\"display:none;\">
    ";
            // line 206
            $this->loadTemplate("forms/dropzone/template.html.twig", "forms/default/form.html.twig", 206)->display($context);
            // line 207
            echo "</div>
";
        }
    }

    // line 55
    public function block_form_classes($context, array $blocks = [])
    {
        // line 56
        echo twig_escape_filter($this->env, ($context["form_outer_classes"] ?? null), "html", null, true);
        echo " ";
        echo twig_escape_filter($this->env, $this->getAttribute(($context["form"] ?? null), "classes", []), "html", null, true);
    }

    // line 61
    public function block_inner_markup_fields_start($context, array $blocks = [])
    {
    }

    // line 65
    public function block_inner_markup_fields_end($context, array $blocks = [])
    {
    }

    // line 69
    public function block_inner_markup_fields($context, array $blocks = [])
    {
        // line 70
        echo "    ";
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable($this->getAttribute(($context["form"] ?? null), "fields", []));
        $context['loop'] = [
          'parent' => $context['_parent'],
          'index0' => 0,
          'index'  => 1,
          'first'  => true,
        ];
        if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof \Countable)) {
            $length = count($context['_seq']);
            $context['loop']['revindex0'] = $length - 1;
            $context['loop']['revindex'] = $length;
            $context['loop']['length'] = $length;
            $context['loop']['last'] = 1 === $length;
        }
        foreach ($context['_seq'] as $context["field_name"] => $context["field"]) {
            // line 71
            echo "      ";
            $context["field"] = $this->env->getExtension('Grav\Plugin\Form\TwigExtension')->prepareFormField($context, $context["field"], $context["field_name"]);
            // line 72
            echo "      ";
            if ($context["field"]) {
                // line 73
                echo "        ";
                $context["value"] = ((($context["form"] ?? null)) ? ($this->getAttribute(($context["form"] ?? null), "value", [0 => $this->getAttribute($context["field"], "name", [])], "method")) : ($this->getAttribute(($context["data"] ?? null), "value", [0 => $this->getAttribute($context["field"], "name", [])], "method")));
                // line 74
                echo "        ";
                $context["field_templates"] = $this->env->getExtension('Grav\Plugin\Form\TwigExtension')->includeFormField($this->getAttribute($context["field"], "type", []), ($context["field_layout"] ?? null));
                // line 75
                echo "
        ";
                // line 76
                $this->displayBlock('inner_markup_field_open', $context, $blocks);
                // line 77
                echo "        ";
                $this->displayBlock('field', $context, $blocks);
                // line 80
                echo "        ";
                $this->displayBlock('inner_markup_field_close', $context, $blocks);
                // line 81
                echo "      ";
            }
            // line 82
            echo "    ";
            ++$context['loop']['index0'];
            ++$context['loop']['index'];
            $context['loop']['first'] = false;
            if (isset($context['loop']['length'])) {
                --$context['loop']['revindex0'];
                --$context['loop']['revindex'];
                $context['loop']['last'] = 0 === $context['loop']['revindex0'];
            }
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['field_name'], $context['field'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 83
        echo "  ";
    }

    // line 76
    public function block_inner_markup_field_open($context, array $blocks = [])
    {
    }

    // line 77
    public function block_field($context, array $blocks = [])
    {
        // line 78
        echo "          ";
        $__internal_d7fc55f1a54b629533d60b43063289db62e68921ee7a5f8de562bd9d4a2b7ad4 = null;
        try {
            $__internal_d7fc55f1a54b629533d60b43063289db62e68921ee7a5f8de562bd9d4a2b7ad4 =             $this->loadTemplate(($context["field_templates"] ?? null), "forms/default/form.html.twig", 78);
        } catch (LoaderError $e) {
            // ignore missing template
        }
        if ($__internal_d7fc55f1a54b629533d60b43063289db62e68921ee7a5f8de562bd9d4a2b7ad4) {
            $__internal_d7fc55f1a54b629533d60b43063289db62e68921ee7a5f8de562bd9d4a2b7ad4->display($context);
        }
        // line 79
        echo "        ";
    }

    // line 80
    public function block_inner_markup_field_close($context, array $blocks = [])
    {
    }

    // line 87
    public function block_inner_markup_buttons_start($context, array $blocks = [])
    {
        // line 88
        echo "  <div class=\"";
        ((($context["form_button_outer_classes"] ?? null)) ? (print (twig_escape_filter($this->env, ($context["form_button_outer_classes"] ?? null), "html", null, true))) : (print ("buttons")));
        echo "\">
  ";
    }

    // line 93
    public function block_inner_markup_buttons_end($context, array $blocks = [])
    {
        // line 94
        echo "  </div>
  ";
    }

    public function getTemplateName()
    {
        return "forms/default/form.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  354 => 94,  351 => 93,  344 => 88,  341 => 87,  336 => 80,  332 => 79,  321 => 78,  318 => 77,  313 => 76,  309 => 83,  295 => 82,  292 => 81,  289 => 80,  286 => 77,  284 => 76,  281 => 75,  278 => 74,  275 => 73,  272 => 72,  269 => 71,  251 => 70,  248 => 69,  243 => 65,  238 => 61,  232 => 56,  229 => 55,  223 => 207,  221 => 206,  218 => 205,  216 => 204,  212 => 202,  210 => 99,  207 => 97,  203 => 93,  201 => 92,  198 => 91,  194 => 87,  192 => 86,  189 => 85,  185 => 69,  183 => 68,  180 => 67,  176 => 65,  174 => 64,  171 => 63,  167 => 61,  165 => 60,  162 => 59,  158 => 55,  156 => 54,  153 => 52,  148 => 48,  146 => 47,  144 => 46,  142 => 45,  140 => 44,  138 => 43,  134 => 40,  131 => 39,  127 => 37,  124 => 36,  121 => 35,  118 => 34,  115 => 33,  113 => 32,  110 => 31,  106 => 29,  104 => 28,  101 => 27,  99 => 26,  97 => 25,  95 => 24,  92 => 23,  85 => 21,  82 => 20,  79 => 19,  75 => 18,  72 => 17,  70 => 16,  68 => 15,  65 => 14,  63 => 13,  61 => 12,  59 => 11,  57 => 10,  55 => 9,  53 => 8,  50 => 7,  48 => 6,  45 => 4,  43 => 3,  41 => 2,  39 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("{% set form = form ?? grav.session.getFlashObject('form') %}
{% set layout = layout ?? form.layout ?? 'default' %}
{% set field_layout = field_layout ?? layout %}

{# Keep here for Backwards Compatibility #}
{% include 'partials/form-messages.html.twig' %}

{% set scope = scope ?: form.scope is defined ? form.scope : 'data.' %}
{% set multipart = '' %}
{% set blueprints = blueprints ?? form.blueprint() %}
{% set method = form.method|upper|default('POST') %}
{% set client_side_validation = form.client_side_validation is not null ? form.client_side_validation : config.plugins.form.client_side_validation|defined(true) %}
{% set inline_errors = form.inline_errors is not null ? form.inline_errors : config.plugins.form.inline_errors(false) %}

{% set data = data ?? form.data %}
{% set context = context ?? data %}

{% for field in form.fields %}
    {% if (method == 'POST' and field.type == 'file') %}
        {% set multipart = ' enctype=\"multipart/form-data\"' %}
    {% endif %}
{% endfor %}

{% set action = action ?? (form.action ?: page.route ~ uri.params) %}
{% set action = (action starts with 'http') or (action starts with '#') ? action : base_url ~ action %}
{% set action = action|trim('/', 'right') %}

{% if (action == base_url_relative) %}
    {% set action = base_url_relative ~ '/' %}
{% endif %}

{% if form.keep_alive %}
    {% if grav.browser.browser == 'msie' and grav.browser.version < 12 %}
        {% do assets.addJs('plugin://form/assets/object.assign.polyfill.js') %}
    {% endif %}
    {% do assets.addJs('plugin://form/assets/form.vendor.js', { 'group': 'bottom', 'loading': 'defer' }) %}
    {% do assets.addJs('plugin://form/assets/form.min.js', { 'group': 'bottom', 'loading': 'defer' }) %}
{% endif %}

{% do assets.addInlineJs(\"
    window.GravForm = window.GravForm || {};
    window.GravForm.config = {
        current_url: '\" ~ grav.route.withoutParams() ~\"',
        current_params: \" ~ grav.route.params|json_encode ~ \",
        param_sep: '\" ~ config.system.param_sep ~ \"',
        base_url_relative: '\" ~ base_url_relative ~ \"',
        form_nonce: '\" ~ form.getNonce() ~ \"',
        session_timeout: \" ~ config.system.session.timeout ~ \"
    };
    window.GravForm.translations = Object.assign({}, window.GravForm.translations || {}, { PLUGIN_FORM: {} });
\", {'group': 'bottom', 'position': 'before', 'priority': 100}) %}

{# Backwards Compatibility for block overrides #}
{% set override_form_classes %}
  {% block form_classes -%}
    {{ form_outer_classes }} {{ form.classes }}
  {%- endblock %}
{% endset %}

{% set override_inner_markup_fields_start %}
  {% block inner_markup_fields_start %}{% endblock %}
{% endset %}

{% set override_inner_markup_fields_end %}
  {% block inner_markup_fields_end %}{% endblock %}
{% endset %}

{% set override_inner_markup_fields %}
  {% block inner_markup_fields %}
    {% for field_name, field in form.fields %}
      {% set field = prepare_form_field(field, field_name) %}
      {% if field %}
        {% set value = form ? form.value(field.name) : data.value(field.name) %}
        {% set field_templates = include_form_field(field.type, field_layout) %}

        {% block inner_markup_field_open %}{% endblock %}
        {% block field %}
          {% include field_templates ignore missing %}
        {% endblock %}
        {% block inner_markup_field_close %}{% endblock %}
      {% endif %}
    {% endfor %}
  {% endblock %}
{% endset %}

{% set override_inner_markup_buttons_start %}
  {% block inner_markup_buttons_start %}
  <div class=\"{{ form_button_outer_classes ?: 'buttons'}}\">
  {% endblock %}
{% endset %}

{% set override_inner_markup_buttons_end %}
  {% block inner_markup_buttons_end %}
  </div>
  {% endblock %}
{% endset %}

{# Embed for HTML layout #}
{% embed 'forms/layouts/form.html.twig' %}

  {% block embed_form_core %}
    name=\"{{ form.name }}\"
    action=\"{{ action }}\"
    method=\"{{ method }}\"{{ multipart|raw }}
    {% if form.id %}id=\"{{ form.id }}\"{% endif %}
    {% if form.novalidate %}novalidate{% endif %}
    {% if form.keep_alive %}data-grav-keepalive=\"true\"{% endif %}
    {% if form.attributes is defined %}
      {% for key,attribute in form.attributes %}
        {% if attribute|of_type('array') %}
          {{ attribute.name }}=\"{{ attribute.value|e('html_attr') }}\"
        {% else %}
          {{ key }}=\"{{ attribute|e('html_attr') }}\"
        {% endif %}
      {% endfor %}
    {% endif %}
  {% endblock %}

  {% block embed_form_classes -%}
    class=\"{{ parent() }} {{ override_form_classes|trim }}\"
  {%- endblock %}

  {% block embed_form_custom_attributes %}
    {% for k, v in blueprints.form.attributes %}
      {{ k }}=\"{{ v|e }}\"
    {% endfor %}
  {% endblock %}

  {% block embed_fields %}
    {{ override_inner_markup_fields_start|raw }}
    {{ override_inner_markup_fields|raw }}

    {% if form.isEnabled() ?? true %}
    {% include include_form_field('formname', field_layout, 'hidden') %}
    {% include include_form_field('formtask', field_layout, 'hidden') %}
    {% include include_form_field('uniqueid', field_layout, 'hidden') %}
    {% include include_form_field('nonce', field_layout, 'hidden') %}
    {% endif %}

    {{ override_inner_markup_fields_end|raw }}
  {% endblock %}

  {% block embed_buttons %}
    {{ override_inner_markup_buttons_start|raw }}

    {% if form.isEnabled() ?? true %}
    {% for button in form.buttons %}
      {% if not button.access or authorize(button.access) %}
      {% if button.outerclasses is defined %}<div class=\"{{ button.outerclasses }}\">{% endif %}

      {% if button.url %}
      {% set button_url = button.url starts with 'http' ? button.url : base_url ~ button.url %}
      {% endif %}

      {% embed 'forms/layouts/button.html.twig' %}
        {% block embed_button_core %}
          {% if button.id %}id=\"{{ button.id }}\"{% endif %}
          {% if button.disabled %}disabled=\"disabled\"{% endif %}
          {% if button.name %}
            name=\"{{ button.name }}\"
          {% else %}
            {% if button.task %}name=\"task\" value=\"{{ button.task }}\"{% endif %}
          {% endif %}
          type=\"{{ button.type|default('submit') }}\"
          {% if button.attributes is defined %}
            {% for key,attribute in button.attributes %}
              {% if attribute|of_type('array') %}
                {{ attribute.name }}=\"{{ attribute.value|e('html_attr') }}\"
              {% else %}
                {{ key }}=\"{{ attribute|e('html_attr') }}\"
              {% endif %}
            {% endfor %}
          {% endif %}
        {% endblock %}

        {% block embed_button_classes %}
          {% block button_classes %}
            class=\"{{ form_button_classes ?: 'button' }} {{ button.classes }}\"
          {% endblock %}
        {% endblock %}

        {% block embed_button_content -%}
          {%- set button_value = button.value|t|default('Submit') -%}
          {%- if button.html -%}
            {{- button_value|trim|raw -}}
          {%- else -%}
            {{- button_value|trim|e -}}
          {%- endif -%}
        {%- endblock %}

      {% endembed %}

      {% if button.outerclasses is defined %}</div>{% endif %}
      {% endif %}
    {% endfor %}
    {% endif %}

    {{ override_inner_markup_buttons_end }}
  {% endblock %}

{% endembed %}


{% if config.forms.dropzone.enabled %}
<div id=\"dropzone-template\" style=\"display:none;\">
    {% include 'forms/dropzone/template.html.twig' %}
</div>
{% endif %}
", "forms/default/form.html.twig", "C:\\wamp\\www\\web-projects\\moon.agency\\user\\plugins\\form\\templates\\forms\\default\\form.html.twig");
    }
}


/* forms/default/form.html.twig */
class __TwigTemplate_ac51e466496834c95be50fa07233a7a0065638ab39bd8898c3d728a818f2f63a___1294676812 extends \Twig\Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->blocks = [
            'embed_form_core' => [$this, 'block_embed_form_core'],
            'embed_form_classes' => [$this, 'block_embed_form_classes'],
            'embed_form_custom_attributes' => [$this, 'block_embed_form_custom_attributes'],
            'embed_fields' => [$this, 'block_embed_fields'],
            'embed_buttons' => [$this, 'block_embed_buttons'],
        ];
    }

    protected function doGetParent(array $context)
    {
        // line 99
        return "forms/layouts/form.html.twig";
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $this->parent = $this->loadTemplate("forms/layouts/form.html.twig", "forms/default/form.html.twig", 99);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 101
    public function block_embed_form_core($context, array $blocks = [])
    {
        // line 102
        echo "    name=\"";
        echo twig_escape_filter($this->env, $this->getAttribute(($context["form"] ?? null), "name", []), "html", null, true);
        echo "\"
    action=\"";
        // line 103
        echo twig_escape_filter($this->env, ($context["action"] ?? null), "html", null, true);
        echo "\"
    method=\"";
        // line 104
        echo twig_escape_filter($this->env, ($context["method"] ?? null), "html", null, true);
        echo "\"";
        echo ($context["multipart"] ?? null);
        echo "
    ";
        // line 105
        if ($this->getAttribute(($context["form"] ?? null), "id", [])) {
            echo "id=\"";
            echo twig_escape_filter($this->env, $this->getAttribute(($context["form"] ?? null), "id", []), "html", null, true);
            echo "\"";
        }
        // line 106
        echo "    ";
        if ($this->getAttribute(($context["form"] ?? null), "novalidate", [])) {
            echo "novalidate";
        }
        // line 107
        echo "    ";
        if ($this->getAttribute(($context["form"] ?? null), "keep_alive", [])) {
            echo "data-grav-keepalive=\"true\"";
        }
        // line 108
        echo "    ";
        if ($this->getAttribute(($context["form"] ?? null), "attributes", [], "any", true, true)) {
            // line 109
            echo "      ";
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable($this->getAttribute(($context["form"] ?? null), "attributes", []));
            foreach ($context['_seq'] as $context["key"] => $context["attribute"]) {
                // line 110
                echo "        ";
                if ($this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->ofTypeFunc($context["attribute"], "array")) {
                    // line 111
                    echo "          ";
                    echo twig_escape_filter($this->env, $this->getAttribute($context["attribute"], "name", []), "html", null, true);
                    echo "=\"";
                    echo twig_escape_filter($this->env, $this->getAttribute($context["attribute"], "value", []), "html_attr");
                    echo "\"
        ";
                } else {
                    // line 113
                    echo "          ";
                    echo twig_escape_filter($this->env, $context["key"], "html", null, true);
                    echo "=\"";
                    echo twig_escape_filter($this->env, $context["attribute"], "html_attr");
                    echo "\"
        ";
                }
                // line 115
                echo "      ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['key'], $context['attribute'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 116
            echo "    ";
        }
        // line 117
        echo "  ";
    }

    // line 119
    public function block_embed_form_classes($context, array $blocks = [])
    {
        // line 120
        echo "class=\"";
        $this->displayParentBlock("embed_form_classes", $context, $blocks);
        echo " ";
        echo twig_escape_filter($this->env, twig_trim_filter(($context["override_form_classes"] ?? null)), "html", null, true);
        echo "\"";
    }

    // line 123
    public function block_embed_form_custom_attributes($context, array $blocks = [])
    {
        // line 124
        echo "    ";
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable($this->getAttribute($this->getAttribute(($context["blueprints"] ?? null), "form", []), "attributes", []));
        foreach ($context['_seq'] as $context["k"] => $context["v"]) {
            // line 125
            echo "      ";
            echo twig_escape_filter($this->env, $context["k"], "html", null, true);
            echo "=\"";
            echo twig_escape_filter($this->env, $context["v"]);
            echo "\"
    ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['k'], $context['v'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 127
        echo "  ";
    }

    // line 129
    public function block_embed_fields($context, array $blocks = [])
    {
        // line 130
        echo "    ";
        echo ($context["override_inner_markup_fields_start"] ?? null);
        echo "
    ";
        // line 131
        echo ($context["override_inner_markup_fields"] ?? null);
        echo "

    ";
        // line 133
        if (((($this->getAttribute(($context["form"] ?? null), "isEnabled", [], "method", true, true) &&  !(null === $this->getAttribute(($context["form"] ?? null), "isEnabled", [], "method")))) ? ($this->getAttribute(($context["form"] ?? null), "isEnabled", [], "method")) : (true))) {
            // line 134
            echo "    ";
            $this->loadTemplate($this->env->getExtension('Grav\Plugin\Form\TwigExtension')->includeFormField("formname", ($context["field_layout"] ?? null), "hidden"), "forms/default/form.html.twig", 134)->display($context);
            // line 135
            echo "    ";
            $this->loadTemplate($this->env->getExtension('Grav\Plugin\Form\TwigExtension')->includeFormField("formtask", ($context["field_layout"] ?? null), "hidden"), "forms/default/form.html.twig", 135)->display($context);
            // line 136
            echo "    ";
            $this->loadTemplate($this->env->getExtension('Grav\Plugin\Form\TwigExtension')->includeFormField("uniqueid", ($context["field_layout"] ?? null), "hidden"), "forms/default/form.html.twig", 136)->display($context);
            // line 137
            echo "    ";
            $this->loadTemplate($this->env->getExtension('Grav\Plugin\Form\TwigExtension')->includeFormField("nonce", ($context["field_layout"] ?? null), "hidden"), "forms/default/form.html.twig", 137)->display($context);
            // line 138
            echo "    ";
        }
        // line 139
        echo "
    ";
        // line 140
        echo ($context["override_inner_markup_fields_end"] ?? null);
        echo "
  ";
    }

    // line 143
    public function block_embed_buttons($context, array $blocks = [])
    {
        // line 144
        echo "    ";
        echo ($context["override_inner_markup_buttons_start"] ?? null);
        echo "

    ";
        // line 146
        if (((($this->getAttribute(($context["form"] ?? null), "isEnabled", [], "method", true, true) &&  !(null === $this->getAttribute(($context["form"] ?? null), "isEnabled", [], "method")))) ? ($this->getAttribute(($context["form"] ?? null), "isEnabled", [], "method")) : (true))) {
            // line 147
            echo "    ";
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable($this->getAttribute(($context["form"] ?? null), "buttons", []));
            $context['loop'] = [
              'parent' => $context['_parent'],
              'index0' => 0,
              'index'  => 1,
              'first'  => true,
            ];
            if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof \Countable)) {
                $length = count($context['_seq']);
                $context['loop']['revindex0'] = $length - 1;
                $context['loop']['revindex'] = $length;
                $context['loop']['length'] = $length;
                $context['loop']['last'] = 1 === $length;
            }
            foreach ($context['_seq'] as $context["_key"] => $context["button"]) {
                // line 148
                echo "      ";
                if (( !$this->getAttribute($context["button"], "access", []) || $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->authorize($this->getAttribute($context["button"], "access", [])))) {
                    // line 149
                    echo "      ";
                    if ($this->getAttribute($context["button"], "outerclasses", [], "any", true, true)) {
                        echo "<div class=\"";
                        echo twig_escape_filter($this->env, $this->getAttribute($context["button"], "outerclasses", []), "html", null, true);
                        echo "\">";
                    }
                    // line 150
                    echo "
      ";
                    // line 151
                    if ($this->getAttribute($context["button"], "url", [])) {
                        // line 152
                        echo "      ";
                        $context["button_url"] = (((is_string($__internal_01476f8db28655ee4ee02ea2d17dd5a92599be76304f08cd8bc0e05aced30666 = $this->getAttribute($context["button"], "url", [])) && is_string($__internal_01c35b74bd85735098add188b3f8372ba465b232ab8298cb582c60f493d3c22e = "http") && ('' === $__internal_01c35b74bd85735098add188b3f8372ba465b232ab8298cb582c60f493d3c22e || 0 === strpos($__internal_01476f8db28655ee4ee02ea2d17dd5a92599be76304f08cd8bc0e05aced30666, $__internal_01c35b74bd85735098add188b3f8372ba465b232ab8298cb582c60f493d3c22e)))) ? ($this->getAttribute($context["button"], "url", [])) : ((($context["base_url"] ?? null) . $this->getAttribute($context["button"], "url", []))));
                        // line 153
                        echo "      ";
                    }
                    // line 154
                    echo "
      ";
                    // line 155
                    $this->loadTemplate("forms/default/form.html.twig", "forms/default/form.html.twig", 155, "856628759")->display($context);
                    // line 192
                    echo "
      ";
                    // line 193
                    if ($this->getAttribute($context["button"], "outerclasses", [], "any", true, true)) {
                        echo "</div>";
                    }
                    // line 194
                    echo "      ";
                }
                // line 195
                echo "    ";
                ++$context['loop']['index0'];
                ++$context['loop']['index'];
                $context['loop']['first'] = false;
                if (isset($context['loop']['length'])) {
                    --$context['loop']['revindex0'];
                    --$context['loop']['revindex'];
                    $context['loop']['last'] = 0 === $context['loop']['revindex0'];
                }
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['button'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 196
            echo "    ";
        }
        // line 197
        echo "
    ";
        // line 198
        echo twig_escape_filter($this->env, ($context["override_inner_markup_buttons_end"] ?? null), "html", null, true);
        echo "
  ";
    }

    public function getTemplateName()
    {
        return "forms/default/form.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  857 => 198,  854 => 197,  851 => 196,  837 => 195,  834 => 194,  830 => 193,  827 => 192,  825 => 155,  822 => 154,  819 => 153,  816 => 152,  814 => 151,  811 => 150,  804 => 149,  801 => 148,  783 => 147,  781 => 146,  775 => 144,  772 => 143,  766 => 140,  763 => 139,  760 => 138,  757 => 137,  754 => 136,  751 => 135,  748 => 134,  746 => 133,  741 => 131,  736 => 130,  733 => 129,  729 => 127,  718 => 125,  713 => 124,  710 => 123,  702 => 120,  699 => 119,  695 => 117,  692 => 116,  686 => 115,  678 => 113,  670 => 111,  667 => 110,  662 => 109,  659 => 108,  654 => 107,  649 => 106,  643 => 105,  637 => 104,  633 => 103,  628 => 102,  625 => 101,  615 => 99,  354 => 94,  351 => 93,  344 => 88,  341 => 87,  336 => 80,  332 => 79,  321 => 78,  318 => 77,  313 => 76,  309 => 83,  295 => 82,  292 => 81,  289 => 80,  286 => 77,  284 => 76,  281 => 75,  278 => 74,  275 => 73,  272 => 72,  269 => 71,  251 => 70,  248 => 69,  243 => 65,  238 => 61,  232 => 56,  229 => 55,  223 => 207,  221 => 206,  218 => 205,  216 => 204,  212 => 202,  210 => 99,  207 => 97,  203 => 93,  201 => 92,  198 => 91,  194 => 87,  192 => 86,  189 => 85,  185 => 69,  183 => 68,  180 => 67,  176 => 65,  174 => 64,  171 => 63,  167 => 61,  165 => 60,  162 => 59,  158 => 55,  156 => 54,  153 => 52,  148 => 48,  146 => 47,  144 => 46,  142 => 45,  140 => 44,  138 => 43,  134 => 40,  131 => 39,  127 => 37,  124 => 36,  121 => 35,  118 => 34,  115 => 33,  113 => 32,  110 => 31,  106 => 29,  104 => 28,  101 => 27,  99 => 26,  97 => 25,  95 => 24,  92 => 23,  85 => 21,  82 => 20,  79 => 19,  75 => 18,  72 => 17,  70 => 16,  68 => 15,  65 => 14,  63 => 13,  61 => 12,  59 => 11,  57 => 10,  55 => 9,  53 => 8,  50 => 7,  48 => 6,  45 => 4,  43 => 3,  41 => 2,  39 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("{% set form = form ?? grav.session.getFlashObject('form') %}
{% set layout = layout ?? form.layout ?? 'default' %}
{% set field_layout = field_layout ?? layout %}

{# Keep here for Backwards Compatibility #}
{% include 'partials/form-messages.html.twig' %}

{% set scope = scope ?: form.scope is defined ? form.scope : 'data.' %}
{% set multipart = '' %}
{% set blueprints = blueprints ?? form.blueprint() %}
{% set method = form.method|upper|default('POST') %}
{% set client_side_validation = form.client_side_validation is not null ? form.client_side_validation : config.plugins.form.client_side_validation|defined(true) %}
{% set inline_errors = form.inline_errors is not null ? form.inline_errors : config.plugins.form.inline_errors(false) %}

{% set data = data ?? form.data %}
{% set context = context ?? data %}

{% for field in form.fields %}
    {% if (method == 'POST' and field.type == 'file') %}
        {% set multipart = ' enctype=\"multipart/form-data\"' %}
    {% endif %}
{% endfor %}

{% set action = action ?? (form.action ?: page.route ~ uri.params) %}
{% set action = (action starts with 'http') or (action starts with '#') ? action : base_url ~ action %}
{% set action = action|trim('/', 'right') %}

{% if (action == base_url_relative) %}
    {% set action = base_url_relative ~ '/' %}
{% endif %}

{% if form.keep_alive %}
    {% if grav.browser.browser == 'msie' and grav.browser.version < 12 %}
        {% do assets.addJs('plugin://form/assets/object.assign.polyfill.js') %}
    {% endif %}
    {% do assets.addJs('plugin://form/assets/form.vendor.js', { 'group': 'bottom', 'loading': 'defer' }) %}
    {% do assets.addJs('plugin://form/assets/form.min.js', { 'group': 'bottom', 'loading': 'defer' }) %}
{% endif %}

{% do assets.addInlineJs(\"
    window.GravForm = window.GravForm || {};
    window.GravForm.config = {
        current_url: '\" ~ grav.route.withoutParams() ~\"',
        current_params: \" ~ grav.route.params|json_encode ~ \",
        param_sep: '\" ~ config.system.param_sep ~ \"',
        base_url_relative: '\" ~ base_url_relative ~ \"',
        form_nonce: '\" ~ form.getNonce() ~ \"',
        session_timeout: \" ~ config.system.session.timeout ~ \"
    };
    window.GravForm.translations = Object.assign({}, window.GravForm.translations || {}, { PLUGIN_FORM: {} });
\", {'group': 'bottom', 'position': 'before', 'priority': 100}) %}

{# Backwards Compatibility for block overrides #}
{% set override_form_classes %}
  {% block form_classes -%}
    {{ form_outer_classes }} {{ form.classes }}
  {%- endblock %}
{% endset %}

{% set override_inner_markup_fields_start %}
  {% block inner_markup_fields_start %}{% endblock %}
{% endset %}

{% set override_inner_markup_fields_end %}
  {% block inner_markup_fields_end %}{% endblock %}
{% endset %}

{% set override_inner_markup_fields %}
  {% block inner_markup_fields %}
    {% for field_name, field in form.fields %}
      {% set field = prepare_form_field(field, field_name) %}
      {% if field %}
        {% set value = form ? form.value(field.name) : data.value(field.name) %}
        {% set field_templates = include_form_field(field.type, field_layout) %}

        {% block inner_markup_field_open %}{% endblock %}
        {% block field %}
          {% include field_templates ignore missing %}
        {% endblock %}
        {% block inner_markup_field_close %}{% endblock %}
      {% endif %}
    {% endfor %}
  {% endblock %}
{% endset %}

{% set override_inner_markup_buttons_start %}
  {% block inner_markup_buttons_start %}
  <div class=\"{{ form_button_outer_classes ?: 'buttons'}}\">
  {% endblock %}
{% endset %}

{% set override_inner_markup_buttons_end %}
  {% block inner_markup_buttons_end %}
  </div>
  {% endblock %}
{% endset %}

{# Embed for HTML layout #}
{% embed 'forms/layouts/form.html.twig' %}

  {% block embed_form_core %}
    name=\"{{ form.name }}\"
    action=\"{{ action }}\"
    method=\"{{ method }}\"{{ multipart|raw }}
    {% if form.id %}id=\"{{ form.id }}\"{% endif %}
    {% if form.novalidate %}novalidate{% endif %}
    {% if form.keep_alive %}data-grav-keepalive=\"true\"{% endif %}
    {% if form.attributes is defined %}
      {% for key,attribute in form.attributes %}
        {% if attribute|of_type('array') %}
          {{ attribute.name }}=\"{{ attribute.value|e('html_attr') }}\"
        {% else %}
          {{ key }}=\"{{ attribute|e('html_attr') }}\"
        {% endif %}
      {% endfor %}
    {% endif %}
  {% endblock %}

  {% block embed_form_classes -%}
    class=\"{{ parent() }} {{ override_form_classes|trim }}\"
  {%- endblock %}

  {% block embed_form_custom_attributes %}
    {% for k, v in blueprints.form.attributes %}
      {{ k }}=\"{{ v|e }}\"
    {% endfor %}
  {% endblock %}

  {% block embed_fields %}
    {{ override_inner_markup_fields_start|raw }}
    {{ override_inner_markup_fields|raw }}

    {% if form.isEnabled() ?? true %}
    {% include include_form_field('formname', field_layout, 'hidden') %}
    {% include include_form_field('formtask', field_layout, 'hidden') %}
    {% include include_form_field('uniqueid', field_layout, 'hidden') %}
    {% include include_form_field('nonce', field_layout, 'hidden') %}
    {% endif %}

    {{ override_inner_markup_fields_end|raw }}
  {% endblock %}

  {% block embed_buttons %}
    {{ override_inner_markup_buttons_start|raw }}

    {% if form.isEnabled() ?? true %}
    {% for button in form.buttons %}
      {% if not button.access or authorize(button.access) %}
      {% if button.outerclasses is defined %}<div class=\"{{ button.outerclasses }}\">{% endif %}

      {% if button.url %}
      {% set button_url = button.url starts with 'http' ? button.url : base_url ~ button.url %}
      {% endif %}

      {% embed 'forms/layouts/button.html.twig' %}
        {% block embed_button_core %}
          {% if button.id %}id=\"{{ button.id }}\"{% endif %}
          {% if button.disabled %}disabled=\"disabled\"{% endif %}
          {% if button.name %}
            name=\"{{ button.name }}\"
          {% else %}
            {% if button.task %}name=\"task\" value=\"{{ button.task }}\"{% endif %}
          {% endif %}
          type=\"{{ button.type|default('submit') }}\"
          {% if button.attributes is defined %}
            {% for key,attribute in button.attributes %}
              {% if attribute|of_type('array') %}
                {{ attribute.name }}=\"{{ attribute.value|e('html_attr') }}\"
              {% else %}
                {{ key }}=\"{{ attribute|e('html_attr') }}\"
              {% endif %}
            {% endfor %}
          {% endif %}
        {% endblock %}

        {% block embed_button_classes %}
          {% block button_classes %}
            class=\"{{ form_button_classes ?: 'button' }} {{ button.classes }}\"
          {% endblock %}
        {% endblock %}

        {% block embed_button_content -%}
          {%- set button_value = button.value|t|default('Submit') -%}
          {%- if button.html -%}
            {{- button_value|trim|raw -}}
          {%- else -%}
            {{- button_value|trim|e -}}
          {%- endif -%}
        {%- endblock %}

      {% endembed %}

      {% if button.outerclasses is defined %}</div>{% endif %}
      {% endif %}
    {% endfor %}
    {% endif %}

    {{ override_inner_markup_buttons_end }}
  {% endblock %}

{% endembed %}


{% if config.forms.dropzone.enabled %}
<div id=\"dropzone-template\" style=\"display:none;\">
    {% include 'forms/dropzone/template.html.twig' %}
</div>
{% endif %}
", "forms/default/form.html.twig", "C:\\wamp\\www\\web-projects\\moon.agency\\user\\plugins\\form\\templates\\forms\\default\\form.html.twig");
    }
}


/* forms/default/form.html.twig */
class __TwigTemplate_ac51e466496834c95be50fa07233a7a0065638ab39bd8898c3d728a818f2f63a___856628759 extends \Twig\Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->blocks = [
            'embed_button_core' => [$this, 'block_embed_button_core'],
            'embed_button_classes' => [$this, 'block_embed_button_classes'],
            'button_classes' => [$this, 'block_button_classes'],
            'embed_button_content' => [$this, 'block_embed_button_content'],
        ];
    }

    protected function doGetParent(array $context)
    {
        // line 155
        return "forms/layouts/button.html.twig";
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $this->parent = $this->loadTemplate("forms/layouts/button.html.twig", "forms/default/form.html.twig", 155);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 156
    public function block_embed_button_core($context, array $blocks = [])
    {
        // line 157
        echo "          ";
        if ($this->getAttribute(($context["button"] ?? null), "id", [])) {
            echo "id=\"";
            echo twig_escape_filter($this->env, $this->getAttribute(($context["button"] ?? null), "id", []), "html", null, true);
            echo "\"";
        }
        // line 158
        echo "          ";
        if ($this->getAttribute(($context["button"] ?? null), "disabled", [])) {
            echo "disabled=\"disabled\"";
        }
        // line 159
        echo "          ";
        if ($this->getAttribute(($context["button"] ?? null), "name", [])) {
            // line 160
            echo "            name=\"";
            echo twig_escape_filter($this->env, $this->getAttribute(($context["button"] ?? null), "name", []), "html", null, true);
            echo "\"
          ";
        } else {
            // line 162
            echo "            ";
            if ($this->getAttribute(($context["button"] ?? null), "task", [])) {
                echo "name=\"task\" value=\"";
                echo twig_escape_filter($this->env, $this->getAttribute(($context["button"] ?? null), "task", []), "html", null, true);
                echo "\"";
            }
            // line 163
            echo "          ";
        }
        // line 164
        echo "          type=\"";
        echo twig_escape_filter($this->env, (($this->getAttribute(($context["button"] ?? null), "type", [], "any", true, true)) ? (_twig_default_filter($this->getAttribute(($context["button"] ?? null), "type", []), "submit")) : ("submit")), "html", null, true);
        echo "\"
          ";
        // line 165
        if ($this->getAttribute(($context["button"] ?? null), "attributes", [], "any", true, true)) {
            // line 166
            echo "            ";
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable($this->getAttribute(($context["button"] ?? null), "attributes", []));
            foreach ($context['_seq'] as $context["key"] => $context["attribute"]) {
                // line 167
                echo "              ";
                if ($this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->ofTypeFunc($context["attribute"], "array")) {
                    // line 168
                    echo "                ";
                    echo twig_escape_filter($this->env, $this->getAttribute($context["attribute"], "name", []), "html", null, true);
                    echo "=\"";
                    echo twig_escape_filter($this->env, $this->getAttribute($context["attribute"], "value", []), "html_attr");
                    echo "\"
              ";
                } else {
                    // line 170
                    echo "                ";
                    echo twig_escape_filter($this->env, $context["key"], "html", null, true);
                    echo "=\"";
                    echo twig_escape_filter($this->env, $context["attribute"], "html_attr");
                    echo "\"
              ";
                }
                // line 172
                echo "            ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['key'], $context['attribute'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 173
            echo "          ";
        }
        // line 174
        echo "        ";
    }

    // line 176
    public function block_embed_button_classes($context, array $blocks = [])
    {
        // line 177
        echo "          ";
        $this->displayBlock('button_classes', $context, $blocks);
        // line 180
        echo "        ";
    }

    // line 177
    public function block_button_classes($context, array $blocks = [])
    {
        // line 178
        echo "            class=\"";
        ((($context["form_button_classes"] ?? null)) ? (print (twig_escape_filter($this->env, ($context["form_button_classes"] ?? null), "html", null, true))) : (print ("button")));
        echo " ";
        echo twig_escape_filter($this->env, $this->getAttribute(($context["button"] ?? null), "classes", []), "html", null, true);
        echo "\"
          ";
    }

    // line 182
    public function block_embed_button_content($context, array $blocks = [])
    {
        // line 183
        $context["button_value"] = _twig_default_filter($this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->translate($this->env, $this->getAttribute(($context["button"] ?? null), "value", [])), "Submit");
        // line 184
        if ($this->getAttribute(($context["button"] ?? null), "html", [])) {
            // line 185
            echo twig_trim_filter(($context["button_value"] ?? null));
        } else {
            // line 187
            echo twig_escape_filter($this->env, twig_trim_filter(($context["button_value"] ?? null)));
        }
    }

    public function getTemplateName()
    {
        return "forms/default/form.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  1238 => 187,  1235 => 185,  1233 => 184,  1231 => 183,  1228 => 182,  1219 => 178,  1216 => 177,  1212 => 180,  1209 => 177,  1206 => 176,  1202 => 174,  1199 => 173,  1193 => 172,  1185 => 170,  1177 => 168,  1174 => 167,  1169 => 166,  1167 => 165,  1162 => 164,  1159 => 163,  1152 => 162,  1146 => 160,  1143 => 159,  1138 => 158,  1131 => 157,  1128 => 156,  1118 => 155,  857 => 198,  854 => 197,  851 => 196,  837 => 195,  834 => 194,  830 => 193,  827 => 192,  825 => 155,  822 => 154,  819 => 153,  816 => 152,  814 => 151,  811 => 150,  804 => 149,  801 => 148,  783 => 147,  781 => 146,  775 => 144,  772 => 143,  766 => 140,  763 => 139,  760 => 138,  757 => 137,  754 => 136,  751 => 135,  748 => 134,  746 => 133,  741 => 131,  736 => 130,  733 => 129,  729 => 127,  718 => 125,  713 => 124,  710 => 123,  702 => 120,  699 => 119,  695 => 117,  692 => 116,  686 => 115,  678 => 113,  670 => 111,  667 => 110,  662 => 109,  659 => 108,  654 => 107,  649 => 106,  643 => 105,  637 => 104,  633 => 103,  628 => 102,  625 => 101,  615 => 99,  354 => 94,  351 => 93,  344 => 88,  341 => 87,  336 => 80,  332 => 79,  321 => 78,  318 => 77,  313 => 76,  309 => 83,  295 => 82,  292 => 81,  289 => 80,  286 => 77,  284 => 76,  281 => 75,  278 => 74,  275 => 73,  272 => 72,  269 => 71,  251 => 70,  248 => 69,  243 => 65,  238 => 61,  232 => 56,  229 => 55,  223 => 207,  221 => 206,  218 => 205,  216 => 204,  212 => 202,  210 => 99,  207 => 97,  203 => 93,  201 => 92,  198 => 91,  194 => 87,  192 => 86,  189 => 85,  185 => 69,  183 => 68,  180 => 67,  176 => 65,  174 => 64,  171 => 63,  167 => 61,  165 => 60,  162 => 59,  158 => 55,  156 => 54,  153 => 52,  148 => 48,  146 => 47,  144 => 46,  142 => 45,  140 => 44,  138 => 43,  134 => 40,  131 => 39,  127 => 37,  124 => 36,  121 => 35,  118 => 34,  115 => 33,  113 => 32,  110 => 31,  106 => 29,  104 => 28,  101 => 27,  99 => 26,  97 => 25,  95 => 24,  92 => 23,  85 => 21,  82 => 20,  79 => 19,  75 => 18,  72 => 17,  70 => 16,  68 => 15,  65 => 14,  63 => 13,  61 => 12,  59 => 11,  57 => 10,  55 => 9,  53 => 8,  50 => 7,  48 => 6,  45 => 4,  43 => 3,  41 => 2,  39 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("{% set form = form ?? grav.session.getFlashObject('form') %}
{% set layout = layout ?? form.layout ?? 'default' %}
{% set field_layout = field_layout ?? layout %}

{# Keep here for Backwards Compatibility #}
{% include 'partials/form-messages.html.twig' %}

{% set scope = scope ?: form.scope is defined ? form.scope : 'data.' %}
{% set multipart = '' %}
{% set blueprints = blueprints ?? form.blueprint() %}
{% set method = form.method|upper|default('POST') %}
{% set client_side_validation = form.client_side_validation is not null ? form.client_side_validation : config.plugins.form.client_side_validation|defined(true) %}
{% set inline_errors = form.inline_errors is not null ? form.inline_errors : config.plugins.form.inline_errors(false) %}

{% set data = data ?? form.data %}
{% set context = context ?? data %}

{% for field in form.fields %}
    {% if (method == 'POST' and field.type == 'file') %}
        {% set multipart = ' enctype=\"multipart/form-data\"' %}
    {% endif %}
{% endfor %}

{% set action = action ?? (form.action ?: page.route ~ uri.params) %}
{% set action = (action starts with 'http') or (action starts with '#') ? action : base_url ~ action %}
{% set action = action|trim('/', 'right') %}

{% if (action == base_url_relative) %}
    {% set action = base_url_relative ~ '/' %}
{% endif %}

{% if form.keep_alive %}
    {% if grav.browser.browser == 'msie' and grav.browser.version < 12 %}
        {% do assets.addJs('plugin://form/assets/object.assign.polyfill.js') %}
    {% endif %}
    {% do assets.addJs('plugin://form/assets/form.vendor.js', { 'group': 'bottom', 'loading': 'defer' }) %}
    {% do assets.addJs('plugin://form/assets/form.min.js', { 'group': 'bottom', 'loading': 'defer' }) %}
{% endif %}

{% do assets.addInlineJs(\"
    window.GravForm = window.GravForm || {};
    window.GravForm.config = {
        current_url: '\" ~ grav.route.withoutParams() ~\"',
        current_params: \" ~ grav.route.params|json_encode ~ \",
        param_sep: '\" ~ config.system.param_sep ~ \"',
        base_url_relative: '\" ~ base_url_relative ~ \"',
        form_nonce: '\" ~ form.getNonce() ~ \"',
        session_timeout: \" ~ config.system.session.timeout ~ \"
    };
    window.GravForm.translations = Object.assign({}, window.GravForm.translations || {}, { PLUGIN_FORM: {} });
\", {'group': 'bottom', 'position': 'before', 'priority': 100}) %}

{# Backwards Compatibility for block overrides #}
{% set override_form_classes %}
  {% block form_classes -%}
    {{ form_outer_classes }} {{ form.classes }}
  {%- endblock %}
{% endset %}

{% set override_inner_markup_fields_start %}
  {% block inner_markup_fields_start %}{% endblock %}
{% endset %}

{% set override_inner_markup_fields_end %}
  {% block inner_markup_fields_end %}{% endblock %}
{% endset %}

{% set override_inner_markup_fields %}
  {% block inner_markup_fields %}
    {% for field_name, field in form.fields %}
      {% set field = prepare_form_field(field, field_name) %}
      {% if field %}
        {% set value = form ? form.value(field.name) : data.value(field.name) %}
        {% set field_templates = include_form_field(field.type, field_layout) %}

        {% block inner_markup_field_open %}{% endblock %}
        {% block field %}
          {% include field_templates ignore missing %}
        {% endblock %}
        {% block inner_markup_field_close %}{% endblock %}
      {% endif %}
    {% endfor %}
  {% endblock %}
{% endset %}

{% set override_inner_markup_buttons_start %}
  {% block inner_markup_buttons_start %}
  <div class=\"{{ form_button_outer_classes ?: 'buttons'}}\">
  {% endblock %}
{% endset %}

{% set override_inner_markup_buttons_end %}
  {% block inner_markup_buttons_end %}
  </div>
  {% endblock %}
{% endset %}

{# Embed for HTML layout #}
{% embed 'forms/layouts/form.html.twig' %}

  {% block embed_form_core %}
    name=\"{{ form.name }}\"
    action=\"{{ action }}\"
    method=\"{{ method }}\"{{ multipart|raw }}
    {% if form.id %}id=\"{{ form.id }}\"{% endif %}
    {% if form.novalidate %}novalidate{% endif %}
    {% if form.keep_alive %}data-grav-keepalive=\"true\"{% endif %}
    {% if form.attributes is defined %}
      {% for key,attribute in form.attributes %}
        {% if attribute|of_type('array') %}
          {{ attribute.name }}=\"{{ attribute.value|e('html_attr') }}\"
        {% else %}
          {{ key }}=\"{{ attribute|e('html_attr') }}\"
        {% endif %}
      {% endfor %}
    {% endif %}
  {% endblock %}

  {% block embed_form_classes -%}
    class=\"{{ parent() }} {{ override_form_classes|trim }}\"
  {%- endblock %}

  {% block embed_form_custom_attributes %}
    {% for k, v in blueprints.form.attributes %}
      {{ k }}=\"{{ v|e }}\"
    {% endfor %}
  {% endblock %}

  {% block embed_fields %}
    {{ override_inner_markup_fields_start|raw }}
    {{ override_inner_markup_fields|raw }}

    {% if form.isEnabled() ?? true %}
    {% include include_form_field('formname', field_layout, 'hidden') %}
    {% include include_form_field('formtask', field_layout, 'hidden') %}
    {% include include_form_field('uniqueid', field_layout, 'hidden') %}
    {% include include_form_field('nonce', field_layout, 'hidden') %}
    {% endif %}

    {{ override_inner_markup_fields_end|raw }}
  {% endblock %}

  {% block embed_buttons %}
    {{ override_inner_markup_buttons_start|raw }}

    {% if form.isEnabled() ?? true %}
    {% for button in form.buttons %}
      {% if not button.access or authorize(button.access) %}
      {% if button.outerclasses is defined %}<div class=\"{{ button.outerclasses }}\">{% endif %}

      {% if button.url %}
      {% set button_url = button.url starts with 'http' ? button.url : base_url ~ button.url %}
      {% endif %}

      {% embed 'forms/layouts/button.html.twig' %}
        {% block embed_button_core %}
          {% if button.id %}id=\"{{ button.id }}\"{% endif %}
          {% if button.disabled %}disabled=\"disabled\"{% endif %}
          {% if button.name %}
            name=\"{{ button.name }}\"
          {% else %}
            {% if button.task %}name=\"task\" value=\"{{ button.task }}\"{% endif %}
          {% endif %}
          type=\"{{ button.type|default('submit') }}\"
          {% if button.attributes is defined %}
            {% for key,attribute in button.attributes %}
              {% if attribute|of_type('array') %}
                {{ attribute.name }}=\"{{ attribute.value|e('html_attr') }}\"
              {% else %}
                {{ key }}=\"{{ attribute|e('html_attr') }}\"
              {% endif %}
            {% endfor %}
          {% endif %}
        {% endblock %}

        {% block embed_button_classes %}
          {% block button_classes %}
            class=\"{{ form_button_classes ?: 'button' }} {{ button.classes }}\"
          {% endblock %}
        {% endblock %}

        {% block embed_button_content -%}
          {%- set button_value = button.value|t|default('Submit') -%}
          {%- if button.html -%}
            {{- button_value|trim|raw -}}
          {%- else -%}
            {{- button_value|trim|e -}}
          {%- endif -%}
        {%- endblock %}

      {% endembed %}

      {% if button.outerclasses is defined %}</div>{% endif %}
      {% endif %}
    {% endfor %}
    {% endif %}

    {{ override_inner_markup_buttons_end }}
  {% endblock %}

{% endembed %}


{% if config.forms.dropzone.enabled %}
<div id=\"dropzone-template\" style=\"display:none;\">
    {% include 'forms/dropzone/template.html.twig' %}
</div>
{% endif %}
", "forms/default/form.html.twig", "C:\\wamp\\www\\web-projects\\moon.agency\\user\\plugins\\form\\templates\\forms\\default\\form.html.twig");
    }
}
