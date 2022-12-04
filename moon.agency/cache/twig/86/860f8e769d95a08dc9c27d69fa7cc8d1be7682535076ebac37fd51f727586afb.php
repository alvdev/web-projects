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

/* list.html.twig */
class __TwigTemplate_f5b2b3290885f952f8de05b42a8cff11d944780ef2c5ad5d86170faf869488b5 extends \Twig\Template
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
        $context["template_body_classes"] = "is-singular logged-in not-front-page page page-id-2 page-template-default customize-support not-scrolled";
        // line 4
        $context["options"] = ["items" => ["@page.children" => "/models"]];
        // line 5
        $context["models"] = $this->getAttribute(($context["page"] ?? null), "collection", [0 => ($context["options"] ?? null)], "method");
        // line 1
        $this->parent = $this->loadTemplate("partials/base.html.twig", "list.html.twig", 1);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 7
    public function block_content($context, array $blocks = [])
    {
        // line 8
        echo "    <main id=\"main\" class=\"site-main clearfix\" role=\"main\">
        <article class=\"page type-page status-publish hentry\">
            <div class=\"entry-inner\">
                <div class=\"entry-content\" itemprop=\"description\">
                    <section class=\"ds page_models models_portrait gorizontal_padding section_padding_70\">
                        <div class=\"container-fluid\">
                            <div class=\"isotope_container isotope row masonry-layout\" data-filters=\".isotope_filters\">
                                ";
        // line 15
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["models"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["model"]) {
            // line 16
            echo "                                    <div class=\"isotope-item col-lg-3 col-md-4 col-sm-6 fashion\">
                                        <div class=\"vertical-item content-absolute\">
                                            <div class=\"item-media\">
                                                ";
            // line 19
            if (twig_first($this->env, $this->getAttribute($this->getAttribute($context["model"], "media", []), "images", []))) {
                // line 20
                echo "                                                    ";
                echo $this->getAttribute($this->getAttribute(twig_first($this->env, $this->getAttribute($this->getAttribute($context["model"], "media", []), "images", [])), "cropZoom", [0 => 338, 1 => 451], "method"), "html", [0 => "", 1 => "", 2 => "attachment-receptar-featured size-receptar-featured wp-post-image"], "method");
                echo "
                                                ";
            } else {
                // line 22
                echo "                                                    <img class=\"attachment-receptar-featured size-receptar-featured wp-post-image\" src=\"";
                echo twig_escape_filter($this->env, ($context["theme_url"] ?? null), "html", null, true);
                echo "/images/";
                echo twig_escape_filter($this->env, $this->getAttribute(($context["site"] ?? null), "global_featured_image", []), "html", null, true);
                echo "\">
                                                ";
            }
            // line 24
            echo "                                            </div>
                                        </div>
                                        <div class=\"item-title text-center\">
                                            <h4>
                                                <a href=\"";
            // line 28
            echo twig_escape_filter($this->env, $this->getAttribute($context["model"], "url", []), "html", null, true);
            echo "\">";
            echo twig_escape_filter($this->env, $this->getAttribute($context["model"], "title", []), "html", null, true);
            echo "</a>
                                            </h4>
                                        </div>
                                    </div>
                                ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['model'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 33
        echo "                            </div>
                        </section>
                    </div>
                </div>
            </article>
        </main>
    ";
    }

    public function getTemplateName()
    {
        return "list.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  102 => 33,  89 => 28,  83 => 24,  75 => 22,  69 => 20,  67 => 19,  62 => 16,  58 => 15,  49 => 8,  46 => 7,  41 => 1,  39 => 5,  37 => 4,  35 => 2,  29 => 1,);
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
{% set template_body_classes = 'is-singular logged-in not-front-page page page-id-2 page-template-default customize-support not-scrolled' %}

{% set options = {items: {'@page.children': '/models'}} %}
{% set models = page.collection(options) %}

{% block content %}
    <main id=\"main\" class=\"site-main clearfix\" role=\"main\">
        <article class=\"page type-page status-publish hentry\">
            <div class=\"entry-inner\">
                <div class=\"entry-content\" itemprop=\"description\">
                    <section class=\"ds page_models models_portrait gorizontal_padding section_padding_70\">
                        <div class=\"container-fluid\">
                            <div class=\"isotope_container isotope row masonry-layout\" data-filters=\".isotope_filters\">
                                {% for model in models %}
                                    <div class=\"isotope-item col-lg-3 col-md-4 col-sm-6 fashion\">
                                        <div class=\"vertical-item content-absolute\">
                                            <div class=\"item-media\">
                                                {% if model.media.images|first %}
                                                    {{ model.media.images|first.cropZoom(338, 451).html('', '', 'attachment-receptar-featured size-receptar-featured wp-post-image')|raw }}
                                                {% else %}
                                                    <img class=\"attachment-receptar-featured size-receptar-featured wp-post-image\" src=\"{{ theme_url }}/images/{{ site.global_featured_image }}\">
                                                {% endif %}
                                            </div>
                                        </div>
                                        <div class=\"item-title text-center\">
                                            <h4>
                                                <a href=\"{{ model.url }}\">{{ model.title }}</a>
                                            </h4>
                                        </div>
                                    </div>
                                {% endfor %}
                            </div>
                        </section>
                    </div>
                </div>
            </article>
        </main>
    {% endblock %}
", "list.html.twig", "C:\\wamp\\www\\web-projects\\moon.agency\\user\\themes\\moon\\templates\\list.html.twig");
    }
}
