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

/* partials/base.html.twig */
class __TwigTemplate_a2ccd3a8e66aec221334894940fbc56e72795746f7cf23be5774445a4a17e5df extends \Twig\Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
            'head' => [$this, 'block_head'],
            'stylesheets' => [$this, 'block_stylesheets'],
            'javascripts' => [$this, 'block_javascripts'],
            'assets' => [$this, 'block_assets'],
            'header' => [$this, 'block_header'],
            'slider' => [$this, 'block_slider'],
            'body' => [$this, 'block_body'],
            'content' => [$this, 'block_content'],
            'footer' => [$this, 'block_footer'],
        ];
        $this->deferred = $this->env->getExtension('Twig\DeferredExtension\DeferredExtension');
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        // line 1
        $context["theme_config"] = $this->getAttribute($this->getAttribute(($context["config"] ?? null), "themes", []), $this->getAttribute($this->getAttribute($this->getAttribute(($context["config"] ?? null), "system", []), "pages", []), "theme", []));
        // line 2
        echo "
<!DOCTYPE html>

<html lang=\"";
        // line 5
        echo twig_escape_filter($this->env, (($this->getAttribute($this->getAttribute(($context["grav"] ?? null), "language", []), "getActive", [])) ? ($this->getAttribute($this->getAttribute(($context["grav"] ?? null), "language", []), "getActive", [])) : ($this->getAttribute($this->getAttribute($this->getAttribute(($context["grav"] ?? null), "config", []), "site", []), "default_lang", []))), "html", null, true);
        echo "\" class='";
        if (((($this->getAttribute(($context["browser"] ?? null), "getBrowser", []) == "msie") && ($this->getAttribute(($context["browser"] ?? null), "getVersion", []) == 7)) && ($this->getAttribute(($context["browser"] ?? null), "getVersion", []) == 8))) {
        } else {
            echo "v2";
        }
        echo " ";
        if ((($this->getAttribute(($context["browser"] ?? null), "getBrowser", []) == "msie") && ($this->getAttribute(($context["browser"] ?? null), "getVersion", []) == 7))) {
            echo "ie ie7 ltie8 ltie9";
        }
        echo " ";
        if ((($this->getAttribute(($context["browser"] ?? null), "getBrowser", []) == "msie") && ($this->getAttribute(($context["browser"] ?? null), "getVersion", []) == 8))) {
            echo "ie ie8 ltie9";
        }
        echo "'>

    <head>
        ";
        // line 8
        $this->displayBlock('head', $context, $blocks);
        // line 52
        echo "    </head>

    <body
        id=\"top\" class=\"";
        // line 55
        if (($this->getAttribute(($context["page"] ?? null), "template", []) == "home")) {
            echo "template-home ";
        }
        if (($context["template_body_classes"] ?? null)) {
            echo twig_escape_filter($this->env, ($context["template_body_classes"] ?? null), "html", null, true);
        } else {
            echo "fl-builder blog has-featured-posts is-not-singular";
        }
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["page"] ?? null), "header", []), "body_classes", []), "html", null, true);
        echo "\">

        <!-- search modal -->
";
        // line 68
        echo "
        <div id=\"canvas\">
            <div id=\"box_wrapper\">
                ";
        // line 71
        $this->displayBlock('header', $context, $blocks);
        // line 74
        echo "
                ";
        // line 75
        $this->displayBlock('slider', $context, $blocks);
        // line 76
        echo "
                ";
        // line 77
        $this->displayBlock('body', $context, $blocks);
        // line 80
        echo "
                ";
        // line 81
        $this->displayBlock('footer', $context, $blocks);
        // line 84
        echo "            </div>
        </div>

        ";
        // line 87
        echo $this->getAttribute(($context["assets"] ?? null), "js", [0 => "bottom"], "method");
        echo "

    </body>
</html>
";
        $this->deferred->resolve($this, $context, $blocks);
    }

    // line 8
    public function block_head($context, array $blocks = [])
    {
        // line 9
        echo "            <meta charset=\"utf-8\"/>
            <title>
                ";
        // line 11
        if ($this->getAttribute(($context["header"] ?? null), "title", [])) {
            // line 12
            echo "                    ";
            echo twig_escape_filter($this->env, $this->getAttribute(($context["header"] ?? null), "title", []), "html");
            echo "
                    -
                ";
        }
        // line 15
        echo "                ";
        echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->translate($this->env, "SITE.TITLE"), "html");
        echo "</title>

            ";
        // line 17
        $this->loadTemplate("partials/metadata.html.twig", "partials/base.html.twig", 17)->display($context);
        // line 18
        echo "            <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no\">
            <link rel=\"icon\" type=\"image/png\" href=\"";
        // line 19
        echo twig_escape_filter($this->env, ($context["theme_url"] ?? null), "html", null, true);
        echo "/images/favicon.png\"/>
            <meta name=\"google-translate-customization\" content=\"";
        // line 20
        echo twig_escape_filter($this->env, $this->getAttribute(($context["site"] ?? null), "google_translate_token", []), "html", null, true);
        echo "\">
            ";
        // line 21
        $this->displayBlock('stylesheets', $context, $blocks);
        // line 30
        echo "
            ";
        // line 31
        $this->displayBlock('javascripts', $context, $blocks);
        // line 38
        echo "
            ";
        // line 39
        if (($context["singular"] ?? null)) {
            // line 40
            echo "                <style id='receptar-stylesheet-inline-css' type='text/css'>
                    .entry-media {
                        background-image: url(";
            // line 42
            if (twig_first($this->env, $this->getAttribute($this->getAttribute(($context["page"] ?? null), "media", []), "images", []))) {
                echo twig_escape_filter($this->env, $this->getAttribute(twig_first($this->env, $this->getAttribute($this->getAttribute(($context["page"] ?? null), "media", []), "images", [])), "url", []), "html", null, true);
            } else {
                echo twig_escape_filter($this->env, ($context["theme_url"] ?? null), "html", null, true);
                echo "/images/";
                echo twig_escape_filter($this->env, $this->getAttribute(($context["site"] ?? null), "global_featured_image", []), "html", null, true);
            }
            echo ");
                    }
                </style>
            ";
        }
        // line 46
        echo "
            ";
        // line 47
        $this->displayBlock('assets', $context, $blocks);
        // line 51
        echo "        ";
    }

    // line 21
    public function block_stylesheets($context, array $blocks = [])
    {
        // line 22
        echo "                ";
        $this->getAttribute(($context["assets"] ?? null), "addCss", [0 => "theme://css/bootstrap.min.css"], "method");
        // line 23
        echo "                ";
        $this->getAttribute(($context["assets"] ?? null), "addCss", [0 => "theme://css/main.css"], "method");
        // line 24
        echo "                ";
        $this->getAttribute(($context["assets"] ?? null), "addCss", [0 => "theme://css/animations.css"], "method");
        // line 25
        echo "                ";
        $this->getAttribute(($context["assets"] ?? null), "addCss", [0 => "theme://css/fonts.css"], "method");
        // line 26
        echo "                ";
        if (((($this->getAttribute(($context["browser"] ?? null), "getBrowser", []) == "msie") && ($this->getAttribute(($context["browser"] ?? null), "getVersion", []) >= 7)) && ($this->getAttribute(($context["browser"] ?? null), "getVersion", []) <= 8))) {
            // line 27
            echo "                    ";
            $this->getAttribute(($context["assets"] ?? null), "addCss", [0 => "theme://css/ie.css"], "method");
            // line 28
            echo "                ";
        }
        // line 29
        echo "            ";
    }

    // line 31
    public function block_javascripts($context, array $blocks = [])
    {
        // line 32
        echo "                ";
        $this->getAttribute(($context["assets"] ?? null), "add", [0 => "theme://js/vendor/modernizr-2.6.2.min.js"], "method");
        // line 33
        echo "                ";
        $this->getAttribute(($context["assets"] ?? null), "add", [0 => "theme://js/compressed.js"], "method");
        // line 34
        echo "                ";
        $this->getAttribute(($context["assets"] ?? null), "add", [0 => "theme://js/main.js"], "method");
        // line 35
        echo "                ";
        // line 36
        echo "                ";
        $this->getAttribute(($context["assets"] ?? null), "add", [0 => "theme://js/vendor/modernizr-2.6.2.min.js"], "method");
        // line 37
        echo "            ";
    }

    public function block_assets($context, array $blocks = [])
    {
        $this->deferred->defer($this, 'assets');
    }

    // line 47
    public function block_assets_deferred($context, array $blocks = [])
    {
        // line 48
        echo "                ";
        echo $this->getAttribute(($context["assets"] ?? null), "css", [], "method");
        echo "
                ";
        // line 49
        echo $this->getAttribute(($context["assets"] ?? null), "js", [], "method");
        echo "
            ";
        $this->deferred->resolve($this, $context, $blocks);
    }

    // line 71
    public function block_header($context, array $blocks = [])
    {
        // line 72
        echo "                    ";
        $this->loadTemplate("partials/header.html.twig", "partials/base.html.twig", 72)->display($context);
        // line 73
        echo "                ";
    }

    // line 75
    public function block_slider($context, array $blocks = [])
    {
    }

    // line 77
    public function block_body($context, array $blocks = [])
    {
        // line 78
        echo "                    ";
        $this->displayBlock('content', $context, $blocks);
        // line 79
        echo "                ";
    }

    // line 78
    public function block_content($context, array $blocks = [])
    {
    }

    // line 81
    public function block_footer($context, array $blocks = [])
    {
        // line 82
        echo "                    ";
        $this->loadTemplate("partials/footer.html.twig", "partials/base.html.twig", 82)->display($context);
        // line 83
        echo "                ";
    }

    public function getTemplateName()
    {
        return "partials/base.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  302 => 83,  299 => 82,  296 => 81,  291 => 78,  287 => 79,  284 => 78,  281 => 77,  276 => 75,  272 => 73,  269 => 72,  266 => 71,  259 => 49,  254 => 48,  251 => 47,  242 => 37,  239 => 36,  237 => 35,  234 => 34,  231 => 33,  228 => 32,  225 => 31,  221 => 29,  218 => 28,  215 => 27,  212 => 26,  209 => 25,  206 => 24,  203 => 23,  200 => 22,  197 => 21,  193 => 51,  191 => 47,  188 => 46,  175 => 42,  171 => 40,  169 => 39,  166 => 38,  164 => 31,  161 => 30,  159 => 21,  155 => 20,  151 => 19,  148 => 18,  146 => 17,  140 => 15,  133 => 12,  131 => 11,  127 => 9,  124 => 8,  114 => 87,  109 => 84,  107 => 81,  104 => 80,  102 => 77,  99 => 76,  97 => 75,  94 => 74,  92 => 71,  87 => 68,  73 => 55,  68 => 52,  66 => 8,  47 => 5,  42 => 2,  40 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("{% set theme_config = attribute(config.themes, config.system.pages.theme) %}

<!DOCTYPE html>

<html lang=\"{{ grav.language.getActive ?: grav.config.site.default_lang }}\" class='{% if browser.getBrowser == 'msie' and browser.getVersion == 7 and browser.getVersion == 8 %}{% else %}v2{% endif %} {% if browser.getBrowser == 'msie' and browser.getVersion == 7 %}ie ie7 ltie8 ltie9{% endif %} {% if browser.getBrowser == 'msie' and browser.getVersion == 8 %}ie ie8 ltie9{% endif %}'>

    <head>
        {% block head %}
            <meta charset=\"utf-8\"/>
            <title>
                {% if header.title %}
                    {{ header.title|e('html') }}
                    -
                {% endif %}
                {{ 'SITE.TITLE'|t|e('html') }}</title>

            {% include 'partials/metadata.html.twig' %}
            <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no\">
            <link rel=\"icon\" type=\"image/png\" href=\"{{ theme_url }}/images/favicon.png\"/>
            <meta name=\"google-translate-customization\" content=\"{{ site.google_translate_token }}\">
            {% block stylesheets %}
                {% do assets.addCss('theme://css/bootstrap.min.css') %}
                {% do assets.addCss('theme://css/main.css') %}
                {% do assets.addCss('theme://css/animations.css') %}
                {% do assets.addCss('theme://css/fonts.css') %}
                {% if browser.getBrowser == 'msie' and browser.getVersion >= 7 and browser.getVersion <= 8 %}
                    {% do assets.addCss('theme://css/ie.css') %}
                {% endif %}
            {% endblock %}

            {% block javascripts %}
                {% do assets.add('theme://js/vendor/modernizr-2.6.2.min.js') %}
                {% do assets.add('theme://js/compressed.js') %}
                {% do assets.add('theme://js/main.js') %}
                {# {% do assets.add('theme://js/switcher.js') %} #}
                {% do assets.add('theme://js/vendor/modernizr-2.6.2.min.js') %}
            {% endblock %}

            {% if singular %}
                <style id='receptar-stylesheet-inline-css' type='text/css'>
                    .entry-media {
                        background-image: url({% if page.media.images|first %}{{ page.media.images|first.url }}{% else %}{{ theme_url }}/images/{{ site.global_featured_image }}{% endif %});
                    }
                </style>
            {% endif %}

            {% block assets deferred %}
                {{ assets.css()|raw }}
                {{ assets.js()|raw }}
            {% endblock %}
        {% endblock head %}
    </head>

    <body
        id=\"top\" class=\"{% if page.template == 'home' %}template-home {% endif %}{% if template_body_classes %}{{ template_body_classes }}{% else %}fl-builder blog has-featured-posts is-not-singular{% endif %}{{ page.header.body_classes }}\">

        <!-- search modal -->
{#         <div class=\"modal\" tabindex=\"-1\" role=\"dialog\" aria-labelledby=\"search_modal\" id=\"search_modal\">
            <div class=\"widget widget_search\">
                <form method=\"get\" class=\"searchform form-inline\" action=\"#\">
                    <div class=\"form-group\">
                        <input type=\"text\" value=\"\" name=\"search\" class=\"form-control\" placeholder=\"Type search keyword here...\" id=\"modal-search-input\">
                    </div>
                    <button type=\"submit\" class=\"theme_button input_button\">Buscar</button>
                </form>
            </div>
        </div> #}

        <div id=\"canvas\">
            <div id=\"box_wrapper\">
                {% block header %}
                    {% include 'partials/header.html.twig' %}
                {% endblock %}

                {% block slider %}{% endblock %}

                {% block body %}
                    {% block content %}{% endblock %}
                {% endblock %}

                {% block footer %}
                    {% include 'partials/footer.html.twig' %}
                {% endblock %}
            </div>
        </div>

        {{ assets.js('bottom')|raw }}

    </body>
</html>
", "partials/base.html.twig", "C:\\wamp\\www\\web-projects\\moon.agency\\user\\themes\\moon\\templates\\partials\\base.html.twig");
    }
}
