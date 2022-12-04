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

/* error.html.twig */
class __TwigTemplate_6057c59e6e87760869e12aed74480b4002fe623689a50f96c9c356c5f467b6a4 extends \Twig\Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->blocks = [
            'content' => [$this, 'block_content'],
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
        $context["template_body_classes"] = "error404 is-not-singular not-front-page not-scrolled";
        // line 1
        $this->parent = $this->loadTemplate("partials/base.html.twig", "error.html.twig", 1);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 4
    public function block_content($context, array $blocks = [])
    {
        // line 5
        echo "    <section class=\"ds section_padding_100\">
        <div class=\"container\">
            <div class=\"row\">
                <div class=\"col-sm-12 text-center\">
                    <p class=\"not_found\">
                        <span class=\"grey\">";
        // line 10
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["page"] ?? null), "header", []), "http_response_code", []), "html", null, true);
        echo "</span>
                    </p>
                    <h2 class=\"muellerhoff highlight\">";
        // line 12
        echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->translate($this->env, "PAGE_NOT_FOUND"), "html", null, true);
        echo "</h2>
                    ";
        // line 13
        echo $this->getAttribute(($context["page"] ?? null), "content", []);
        echo "
                    <div class=\"divider_20\"></div>

                    <div class=\"row\">
                      <div class=\"widget widget_search\">
                          <form method=\"get\" class=\"searchform form-inline\" action=\"https://html.modernwebtemplates.com/\">
                              <div class=\"form-group\">
                                  <label class=\"sr-only\" for=\"page_search\">Search for:</label>
                                  <input type=\"text\" id=\"page_search\" value=\"\" name=\"search\" class=\"form-control\" placeholder=\"Buscar\">
                              </div>
                              <button type=\"submit\" class=\"theme_button input_button\">Search</button>
                          </form>
                      </div>
                    </div>
                    <p class=\"divider_20\"></p>
                    <a href=\"index.html\" class=\"theme_button inverse\">";
        // line 28
        echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->translate($this->env, "GO_TO_HOME"), "html", null, true);
        echo "</a>
                </div>
            </div>
        </div>
    </section>
";
    }

    public function getTemplateName()
    {
        return "error.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  79 => 28,  61 => 13,  57 => 12,  52 => 10,  45 => 5,  42 => 4,  37 => 1,  35 => 2,  29 => 1,);
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
{% set template_body_classes = 'error404 is-not-singular not-front-page not-scrolled' %}

{% block content %}
    <section class=\"ds section_padding_100\">
        <div class=\"container\">
            <div class=\"row\">
                <div class=\"col-sm-12 text-center\">
                    <p class=\"not_found\">
                        <span class=\"grey\">{{ page.header.http_response_code }}</span>
                    </p>
                    <h2 class=\"muellerhoff highlight\">{{ 'PAGE_NOT_FOUND'|t }}</h2>
                    {{ page.content|raw }}
                    <div class=\"divider_20\"></div>

                    <div class=\"row\">
                      <div class=\"widget widget_search\">
                          <form method=\"get\" class=\"searchform form-inline\" action=\"https://html.modernwebtemplates.com/\">
                              <div class=\"form-group\">
                                  <label class=\"sr-only\" for=\"page_search\">Search for:</label>
                                  <input type=\"text\" id=\"page_search\" value=\"\" name=\"search\" class=\"form-control\" placeholder=\"Buscar\">
                              </div>
                              <button type=\"submit\" class=\"theme_button input_button\">Search</button>
                          </form>
                      </div>
                    </div>
                    <p class=\"divider_20\"></p>
                    <a href=\"index.html\" class=\"theme_button inverse\">{{ 'GO_TO_HOME'|t }}</a>
                </div>
            </div>
        </div>
    </section>
{% endblock %}
", "error.html.twig", "C:\\wamp\\www\\web-projects\\moon.agency\\user\\themes\\moon\\templates\\error.html.twig");
    }
}
