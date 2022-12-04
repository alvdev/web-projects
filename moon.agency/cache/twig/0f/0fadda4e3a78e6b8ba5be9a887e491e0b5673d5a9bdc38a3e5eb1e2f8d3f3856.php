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

/* partials/header.html.twig */
class __TwigTemplate_c602206da403213a5d1d6468657b25537058f9cb8bfe52b39560c80a159bacef extends \Twig\Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
            'navigation' => [$this, 'block_navigation'],
            'breadcrumbs' => [$this, 'block_breadcrumbs'],
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        // line 1
        echo "<header class=\"page_header header_darkgrey columns_padding_0 table_section\">
    <div class=\"container-fluid\">
        <div class=\"row\">
            <div class=\"col-md-2 col-sm-6 text-center\">
                <a href=\"index.html\" class=\"logo logo_image\">
                    <img src=\"";
        // line 6
        echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->urlFunc("theme://images/moon-logo.webp"), "html", null, true);
        echo "\" alt=\"\"/>
                </a>
            </div>
            <div class=\"col-md-8 text-center\">
                ";
        // line 10
        $this->displayBlock('navigation', $context, $blocks);
        // line 13
        echo "                <span class=\"toggle_menu\">
                    <span></span>
                </span>
            </div>
            <div class=\"col-md-2 col-sm-6 header-contacts text-center hidden-xs\">
                <div class=\"highlight inline-block fontsize_30 thin\">";
        // line 18
        echo twig_escape_filter($this->env, $this->getAttribute(($context["theme"] ?? null), "phone", []));
        echo "</div>
                <div class=\"fontsize_20 grey topmargin_-5\">Servicio 24 horas</div>
            </div>
        </div>
    </div>
</header>

";
        // line 25
        if ($this->getAttribute($this->getAttribute($this->getAttribute(($context["config"] ?? null), "plugins", []), "breadcrumbs", []), "enabled", [])) {
            // line 26
            echo "    ";
            if (($this->getAttribute(($context["page"] ?? null), "template", []) != "home")) {
                // line 27
                echo "        ";
                $this->displayBlock('breadcrumbs', $context, $blocks);
                // line 30
                echo "    ";
            }
        }
    }

    // line 10
    public function block_navigation($context, array $blocks = [])
    {
        // line 11
        echo "                    ";
        $this->loadTemplate("partials/navigation.html.twig", "partials/header.html.twig", 11)->display($context);
        // line 12
        echo "                ";
    }

    // line 27
    public function block_breadcrumbs($context, array $blocks = [])
    {
        // line 28
        echo "            ";
        $this->loadTemplate("partials/breadcrumbs.html.twig", "partials/header.html.twig", 28)->display($context);
        // line 29
        echo "        ";
    }

    public function getTemplateName()
    {
        return "partials/header.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  95 => 29,  92 => 28,  89 => 27,  85 => 12,  82 => 11,  79 => 10,  73 => 30,  70 => 27,  67 => 26,  65 => 25,  55 => 18,  48 => 13,  46 => 10,  39 => 6,  32 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("<header class=\"page_header header_darkgrey columns_padding_0 table_section\">
    <div class=\"container-fluid\">
        <div class=\"row\">
            <div class=\"col-md-2 col-sm-6 text-center\">
                <a href=\"index.html\" class=\"logo logo_image\">
                    <img src=\"{{ url('theme://images/moon-logo.webp') }}\" alt=\"\"/>
                </a>
            </div>
            <div class=\"col-md-8 text-center\">
                {% block navigation %}
                    {% include 'partials/navigation.html.twig' %}
                {% endblock %}
                <span class=\"toggle_menu\">
                    <span></span>
                </span>
            </div>
            <div class=\"col-md-2 col-sm-6 header-contacts text-center hidden-xs\">
                <div class=\"highlight inline-block fontsize_30 thin\">{{ theme.phone|e }}</div>
                <div class=\"fontsize_20 grey topmargin_-5\">Servicio 24 horas</div>
            </div>
        </div>
    </div>
</header>

{% if config.plugins.breadcrumbs.enabled %}
    {% if page.template != 'home' %}
        {% block breadcrumbs %}
            {% include \"partials/breadcrumbs.html.twig\" %}
        {% endblock %}
    {% endif %}
{% endif %}
", "partials/header.html.twig", "C:\\wamp\\www\\web-projects\\moon.agency\\user\\themes\\moon\\templates\\partials\\header.html.twig");
    }
}
