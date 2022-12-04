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

/* home.html.twig */
class __TwigTemplate_dd583f329a856b45565d847b0c26e86cbe35cc02e0349dbbe5bb534404cc1b72 extends \Twig\Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->blocks = [
            'content' => [$this, 'block_content'],
            'subheader' => [$this, 'block_subheader'],
            'models' => [$this, 'block_models'],
            'about_us' => [$this, 'block_about_us'],
            'where_are_we' => [$this, 'block_where_are_we'],
            'contact_us' => [$this, 'block_contact_us'],
            'work_with_us' => [$this, 'block_work_with_us'],
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
        $this->parent = $this->loadTemplate("partials/base.html.twig", "home.html.twig", 1);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 6
    public function block_content($context, array $blocks = [])
    {
        // line 7
        echo "    <main id=\"main\" class=\"site-main clearfix\" role=\"main\">
        <article class=\"page type-page status-publish hentry\">
            <div class=\"entry-media\">
                <figure class=\"post-thumbnail\" itemprop=\"image\">
                    <a href=\"";
        // line 11
        echo twig_escape_filter($this->env, $this->getAttribute(($context["page"] ?? null), "url", []), "html", null, true);
        echo "\">
                        ";
        // line 12
        if (($context["banner"] ?? null)) {
            // line 13
            echo "                            ";
            echo $this->getAttribute($this->getAttribute(($context["banner"] ?? null), "cropZoom", [0 => 480, 1 => 640], "method"), "html", [0 => "", 1 => "", 2 => "attachment-thumbnail size-thumbnail wp-post-image"], "method");
            echo "
                        ";
            // line 16
            echo "                        ";
        }
        // line 17
        echo "                    </a>
                </figure>
            </div>
            <div class=\"entry-inner\">
                <div class=\"entry-content\" itemprop=\"description\">
                    ";
        // line 22
        $this->displayBlock('subheader', $context, $blocks);
        // line 25
        echo "
                    ";
        // line 26
        $this->displayBlock('models', $context, $blocks);
        // line 29
        echo "
                    ";
        // line 30
        $this->displayBlock('about_us', $context, $blocks);
        // line 33
        echo "
                    ";
        // line 34
        $this->displayBlock('where_are_we', $context, $blocks);
        // line 37
        echo "
                    ";
        // line 38
        $this->displayBlock('contact_us', $context, $blocks);
        // line 41
        echo "
                    ";
        // line 42
        $this->displayBlock('work_with_us', $context, $blocks);
        // line 45
        echo "                </div>
            </div>
        </article>
    </main>
";
    }

    // line 22
    public function block_subheader($context, array $blocks = [])
    {
        // line 23
        echo "                        ";
        $this->loadTemplate("partials/_subheader.html.twig", "home.html.twig", 23)->display($context);
        // line 24
        echo "                    ";
    }

    // line 26
    public function block_models($context, array $blocks = [])
    {
        // line 27
        echo "                        ";
        $this->loadTemplate("partials/_models.html.twig", "home.html.twig", 27)->display($context);
        // line 28
        echo "                    ";
    }

    // line 30
    public function block_about_us($context, array $blocks = [])
    {
        // line 31
        echo "                        ";
        $this->loadTemplate("partials/_about-us.html.twig", "home.html.twig", 31)->display($context);
        // line 32
        echo "                    ";
    }

    // line 34
    public function block_where_are_we($context, array $blocks = [])
    {
        // line 35
        echo "                        ";
        $this->loadTemplate("partials/_where-are-we.html.twig", "home.html.twig", 35)->display($context);
        // line 36
        echo "                    ";
    }

    // line 38
    public function block_contact_us($context, array $blocks = [])
    {
        // line 39
        echo "                        ";
        $this->loadTemplate("partials/_contact-us.html.twig", "home.html.twig", 39)->display($context);
        // line 40
        echo "                    ";
    }

    // line 42
    public function block_work_with_us($context, array $blocks = [])
    {
        // line 43
        echo "                        ";
        $this->loadTemplate("partials/_work-with-us.html.twig", "home.html.twig", 43)->display($context);
        // line 44
        echo "                    ";
    }

    public function getTemplateName()
    {
        return "home.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  173 => 44,  170 => 43,  167 => 42,  163 => 40,  160 => 39,  157 => 38,  153 => 36,  150 => 35,  147 => 34,  143 => 32,  140 => 31,  137 => 30,  133 => 28,  130 => 27,  127 => 26,  123 => 24,  120 => 23,  117 => 22,  109 => 45,  107 => 42,  104 => 41,  102 => 38,  99 => 37,  97 => 34,  94 => 33,  92 => 30,  89 => 29,  87 => 26,  84 => 25,  82 => 22,  75 => 17,  72 => 16,  67 => 13,  65 => 12,  61 => 11,  55 => 7,  52 => 6,  47 => 1,  45 => 4,  43 => 3,  41 => 2,  35 => 1,);
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
            <div class=\"entry-media\">
                <figure class=\"post-thumbnail\" itemprop=\"image\">
                    <a href=\"{{ page.url }}\">
                        {% if banner %}
                            {{ banner.cropZoom(480, 640).html('', '', 'attachment-thumbnail size-thumbnail wp-post-image')|raw }}
                        {# {% else %}
                            <img class=\"attachment-thumbnail size-thumbnail wp-post-image\" src=\"{{ theme_url }}/images/{{ site.global_featured_image }}\"> #}
                        {% endif %}
                    </a>
                </figure>
            </div>
            <div class=\"entry-inner\">
                <div class=\"entry-content\" itemprop=\"description\">
                    {% block subheader %}
                        {% include \"partials/_subheader.html.twig\" %}
                    {% endblock %}

                    {% block models %}
                        {% include \"partials/_models.html.twig\" %}
                    {% endblock %}

                    {% block about_us %}
                        {% include \"partials/_about-us.html.twig\" %}
                    {% endblock %}

                    {% block where_are_we %}
                        {% include \"partials/_where-are-we.html.twig\" %}
                    {% endblock %}

                    {% block contact_us %}
                        {% include \"partials/_contact-us.html.twig\" %}
                    {% endblock %}

                    {% block work_with_us %}
                        {% include \"partials/_work-with-us.html.twig\" %}
                    {% endblock %}
                </div>
            </div>
        </article>
    </main>
{% endblock %}
", "home.html.twig", "C:\\wamp\\www\\web-projects\\moon.agency\\user\\themes\\moon\\templates\\home.html.twig");
    }
}
