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

/* forms/fields/fieldset/fieldset.html.twig */
class __TwigTemplate_e117f618351d293acdc0c3e22721f32fcf1aba1ce08aea3b6d666c763e1e9fde extends \Twig\Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->blocks = [
            'field' => [$this, 'block_field'],
        ];
    }

    protected function doGetParent(array $context)
    {
        // line 1
        return "forms/field.html.twig";
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        // line 2
        $context["scope"] = (($this->getAttribute(($context["field"] ?? null), "nest_id", [])) ? (((($context["scope"] ?? null) . $this->getAttribute(($context["field"] ?? null), "name", [])) . ".")) : (($context["scope"] ?? null)));
        // line 1
        $this->parent = $this->loadTemplate("forms/field.html.twig", "forms/fields/fieldset/fieldset.html.twig", 1);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 4
    public function block_field($context, array $blocks = [])
    {
        // line 5
        echo "<fieldset ";
        if ($this->getAttribute(($context["field"] ?? null), "id", [], "any", true, true)) {
            echo "id=\"";
            echo twig_escape_filter($this->env, $this->getAttribute(($context["field"] ?? null), "id", []), "html", null, true);
            echo "\"";
        }
        echo " ";
        if ($this->getAttribute(($context["field"] ?? null), "classes", [], "any", true, true)) {
            echo "class=\"";
            echo twig_escape_filter($this->env, $this->getAttribute(($context["field"] ?? null), "classes", []), "html", null, true);
            echo "\" ";
        }
        echo ">
  ";
        // line 6
        if ($this->getAttribute(($context["field"] ?? null), "legend", [])) {
            // line 7
            echo "    <legend>";
            echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->translate($this->env, $this->getAttribute(($context["field"] ?? null), "legend", [])), "html", null, true);
            echo "</legend>
  ";
        }
        // line 9
        echo "
  ";
        // line 10
        $this->loadTemplate("forms/default/fields.html.twig", "forms/fields/fieldset/fieldset.html.twig", 10)->display(twig_array_merge($context, ["name" => $this->getAttribute(($context["field"] ?? null), "name", []), "fields" => $this->getAttribute(($context["field"] ?? null), "fields", [])]));
        // line 11
        echo "</fieldset>
";
    }

    public function getTemplateName()
    {
        return "forms/fields/fieldset/fieldset.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  73 => 11,  71 => 10,  68 => 9,  62 => 7,  60 => 6,  45 => 5,  42 => 4,  37 => 1,  35 => 2,  29 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("{% extends \"forms/field.html.twig\" %}
{% set scope = field.nest_id ? scope ~ field.name ~ '.' : scope %}

{% block field %}
<fieldset {% if field.id is defined %}id=\"{{ field.id }}\"{% endif %} {% if field.classes is defined %}class=\"{{ field.classes }}\" {% endif %}>
  {% if field.legend %}
    <legend>{{ field.legend|t }}</legend>
  {% endif %}

  {% include 'forms/default/fields.html.twig' with {name: field.name, fields: field.fields} %}
</fieldset>
{% endblock %}", "forms/fields/fieldset/fieldset.html.twig", "C:\\wamp\\www\\web-projects\\moon.agency\\user\\plugins\\form\\templates\\forms\\fields\\fieldset\\fieldset.html.twig");
    }
}
