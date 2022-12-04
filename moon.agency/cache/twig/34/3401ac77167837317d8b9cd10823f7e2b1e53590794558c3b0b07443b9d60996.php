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

/* forms/layouts/button/default-button.html.twig */
class __TwigTemplate_128ca6d89e0c62cfeb9d1fc0e6642f20b79ec36ccc766e1682c37d68518e7642 extends \Twig\Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
            'embed_button_core' => [$this, 'block_embed_button_core'],
            'embed_button_classes' => [$this, 'block_embed_button_classes'],
            'embed_button_content' => [$this, 'block_embed_button_content'],
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        // line 1
        ob_start();
        // line 2
        echo "<button ";
        $this->displayBlock('embed_button_core', $context, $blocks);
        echo " ";
        $this->displayBlock('embed_button_classes', $context, $blocks);
        echo ">";
        $this->displayBlock('embed_button_content', $context, $blocks);
        // line 3
        echo "</button>";
        $context["button_tag"] = ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
        if (($context["button_url"] ?? null)) {
            // line 4
            echo "<a href=\"";
            echo twig_escape_filter($this->env, ($context["button_url"] ?? null));
            echo "\">";
            echo twig_trim_filter(($context["button_tag"] ?? null));
            echo "</a>";
        } else {
            // line 5
            echo twig_trim_filter(($context["button_tag"] ?? null));
        }
    }

    // line 2
    public function block_embed_button_core($context, array $blocks = [])
    {
        echo " ";
    }

    public function block_embed_button_classes($context, array $blocks = [])
    {
        echo " ";
    }

    public function block_embed_button_content($context, array $blocks = [])
    {
    }

    public function getTemplateName()
    {
        return "forms/layouts/button/default-button.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  58 => 2,  53 => 5,  46 => 4,  42 => 3,  35 => 2,  33 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("{% set button_tag %}
<button {% block embed_button_core %} {% endblock %} {% block embed_button_classes %} {% endblock %}>{%- block embed_button_content -%}
{%- endblock -%}</button>{% endset %}{% if button_url %}
<a href=\"{{ button_url|e }}\">{{ button_tag|trim|raw }}</a>{% else %}
{{ button_tag|trim|raw }}{% endif %}
", "forms/layouts/button/default-button.html.twig", "C:\\wamp\\www\\web-projects\\moon.agency\\user\\themes\\moon\\templates\\forms\\layouts\\button\\default-button.html.twig");
    }
}
