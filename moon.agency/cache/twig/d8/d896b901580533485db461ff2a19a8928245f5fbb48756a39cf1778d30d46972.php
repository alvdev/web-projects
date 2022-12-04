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

/* contact.html.twig */
class __TwigTemplate_d721a557fc89bcd890d8c88d75558c1c0c27159275a5d16e6cfb3570f0df26ee extends \Twig\Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->blocks = [
            'content' => [$this, 'block_content'],
            'contact_us' => [$this, 'block_contact_us'],
            'where_are_we' => [$this, 'block_where_are_we'],
        ];
    }

    protected function doGetParent(array $context)
    {
        // line 1
        return "partials/base.html.twig";
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        // line 2
        $context["banner"] = twig_first($this->env, $this->getAttribute($this->getAttribute(($context["page"] ?? null), "media", []), "images", []));
        // line 3
        $context["template_body_classes"] = "is-singular logged-in not-front-page page page-id-2 page-template-default customize-support not-scrolled";
        // line 4
        $context["singular"] = true;
        // line 1
        $this->parent = $this->loadTemplate("partials/base.html.twig", "contact.html.twig", 1);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 6
    public function block_content($context, array $blocks = [])
    {
        // line 7
        echo "    <main id=\"main\" class=\"site-main clearfix\" role=\"main\">
        <article class=\"page type-page status-publish hentry\">
            <div class=\"entry-inner\">
                <div class=\"entry-content\" itemprop=\"description\">
                    <section class=\"ds section_padding_70\">
                        <div class=\"container\">
                            <div class=\"row\">
                                <div class=\"col-sm-12 text-center\">
                                    <h2 class=\"big margin_0\">Escríbenos<br>
                                        <span class=\"muellerhoff topmargin_5 highlight\">
                                            Formulario de contacto
                                        </span>
                                    </h2>
                                    <div class=\"medium topmargin_30 bottommargin_50\">";
        // line 20
        echo $this->getAttribute(($context["page"] ?? null), "content", []);
        echo "</div>
                                </div>
                            </div>

                            ";
        // line 24
        $this->displayBlock('contact_us', $context, $blocks);
        // line 27
        echo "                        </div>
                    </section>

                    ";
        // line 30
        $this->displayBlock('where_are_we', $context, $blocks);
        // line 33
        echo "                </div>
            </div>
        </article>
    </main>
";
    }

    // line 24
    public function block_contact_us($context, array $blocks = [])
    {
        // line 25
        echo "                                ";
        $this->loadTemplate("forms/form.html.twig", "contact.html.twig", 25)->display($context);
        // line 26
        echo "                            ";
    }

    // line 30
    public function block_where_are_we($context, array $blocks = [])
    {
        // line 31
        echo "                        ";
        $this->loadTemplate("partials/_where-are-we.html.twig", "contact.html.twig", 31)->display($context);
        // line 32
        echo "                    ";
    }

    public function getTemplateName()
    {
        return "contact.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  106 => 32,  103 => 31,  100 => 30,  96 => 26,  93 => 25,  90 => 24,  82 => 33,  80 => 30,  75 => 27,  73 => 24,  66 => 20,  51 => 7,  48 => 6,  43 => 1,  41 => 4,  39 => 3,  37 => 2,  31 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("{% extends 'partials/base.html.twig' %}
{% set banner = page.media.images|first %}
{% set template_body_classes = 'is-singular logged-in not-front-page page page-id-2 page-template-default customize-support not-scrolled' %}
{% set singular = true %}

{% block content %}
    <main id=\"main\" class=\"site-main clearfix\" role=\"main\">
        <article class=\"page type-page status-publish hentry\">
            <div class=\"entry-inner\">
                <div class=\"entry-content\" itemprop=\"description\">
                    <section class=\"ds section_padding_70\">
                        <div class=\"container\">
                            <div class=\"row\">
                                <div class=\"col-sm-12 text-center\">
                                    <h2 class=\"big margin_0\">Escríbenos<br>
                                        <span class=\"muellerhoff topmargin_5 highlight\">
                                            Formulario de contacto
                                        </span>
                                    </h2>
                                    <div class=\"medium topmargin_30 bottommargin_50\">{{ page.content|raw}}</div>
                                </div>
                            </div>

                            {% block contact_us %}
                                {% include \"forms/form.html.twig\" %}
                            {% endblock %}
                        </div>
                    </section>

                    {% block where_are_we %}
                        {% include \"partials/_where-are-we.html.twig\" %}
                    {% endblock %}
                </div>
            </div>
        </article>
    </main>
{% endblock %}
", "contact.html.twig", "C:\\wamp\\www\\web-projects\\moon.agency\\user\\themes\\moon\\templates\\contact.html.twig");
    }
}
