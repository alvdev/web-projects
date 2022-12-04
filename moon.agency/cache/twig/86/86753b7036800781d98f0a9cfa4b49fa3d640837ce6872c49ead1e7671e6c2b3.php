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

/* faq.html.twig */
class __TwigTemplate_2eb689a28a703aa7a134f1779d490cab17ff5cf62838915a4cc6f2b6da0e91ae extends \Twig\Template
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
        // line 1
        $this->parent = $this->loadTemplate("partials/base.html.twig", "faq.html.twig", 1);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 4
    public function block_content($context, array $blocks = [])
    {
        // line 5
        echo "    <main id=\"main\" class=\"site-main clearfix\" role=\"main\">
        <article class=\"page type-page status-publish hentry\">
            <div class=\"entry-inner\">
                <div class=\"entry-content\" itemprop=\"description\">
                    <section class=\"ds section_padding_70 section_padding_bottom_50\">
                        <div class=\"container\">
                            <div class=\"row bottommargin_20\">
                                <div class=\"col-sm-12 text-center\">
                                    <h2 class=\"big topmargin_0 bottommargin_40\">
                                        ";
        // line 14
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["page"] ?? null), "header", []), "title", []), "html", null, true);
        echo "
                                    </h2>
                                    <div class=\"fontsize_20 maxwidth_570 inline-block\">
                                        ";
        // line 17
        echo $this->getAttribute(($context["page"] ?? null), "content", []);
        echo "
                                    </div>
                                </div>
                            </div>

                            <div class=\"row\">
                                ";
        // line 23
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(twig_array_batch($this->getAttribute($this->getAttribute(($context["page"] ?? null), "header", []), "faqs", []), 4));
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
        foreach ($context['_seq'] as $context["_key"] => $context["col"]) {
            // line 24
            echo "                                    <div class=\"col-sm-6\">
                                        <div class=\"panel-group\" id=\"accordion";
            // line 25
            echo twig_escape_filter($this->env, $this->getAttribute($context["loop"], "index", []), "html", null, true);
            echo "\">
                                            ";
            // line 26
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable($context["col"]);
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
            foreach ($context['_seq'] as $context["_key"] => $context["item"]) {
                // line 27
                echo "                                                <div class=\"panel panel-default\">
                                                    <div class=\"panel-heading\">
                                                        <h4 class=\"panel-title\">
                                                            <a data-toggle=\"collapse\" data-parent=\"#accordion";
                // line 30
                echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($this->getAttribute($context["loop"], "parent", []), "loop", []), "index", []), "html", null, true);
                echo "\" href=\"#collapse";
                echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($this->getAttribute($context["loop"], "parent", []), "loop", []), "index", []), "html", null, true);
                echo twig_escape_filter($this->env, $this->getAttribute($context["loop"], "index", []), "html", null, true);
                echo "\" class=\"collapsed\">
                                                                <i class=\"rt-icon2-bubble highlight\"></i>
                                                                ";
                // line 32
                echo twig_escape_filter($this->env, $this->getAttribute($context["item"], "question", []), "html", null, true);
                echo "
                                                            </a>
                                                        </h4>
                                                    </div>
                                                    <div id=\"collapse";
                // line 36
                echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($this->getAttribute($context["loop"], "parent", []), "loop", []), "index", []), "html", null, true);
                echo twig_escape_filter($this->env, $this->getAttribute($context["loop"], "index", []), "html", null, true);
                echo "\" class=\"panel-collapse collapse\">
                                                        <div class=\"panel-body\">
                                                            ";
                // line 38
                echo twig_escape_filter($this->env, $this->getAttribute($context["item"], "answer", []), "html", null, true);
                echo "
                                                        </div>
                                                    </div>
                                                </div>
                                            ";
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
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['item'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 43
            echo "                                        </div>
                                    </div>
                                ";
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
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['col'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 46
        echo "                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </article>
    </main>
";
    }

    public function getTemplateName()
    {
        return "faq.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  173 => 46,  157 => 43,  138 => 38,  132 => 36,  125 => 32,  117 => 30,  112 => 27,  95 => 26,  91 => 25,  88 => 24,  71 => 23,  62 => 17,  56 => 14,  45 => 5,  42 => 4,  37 => 1,  35 => 2,  29 => 1,);
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

{% block content %}
    <main id=\"main\" class=\"site-main clearfix\" role=\"main\">
        <article class=\"page type-page status-publish hentry\">
            <div class=\"entry-inner\">
                <div class=\"entry-content\" itemprop=\"description\">
                    <section class=\"ds section_padding_70 section_padding_bottom_50\">
                        <div class=\"container\">
                            <div class=\"row bottommargin_20\">
                                <div class=\"col-sm-12 text-center\">
                                    <h2 class=\"big topmargin_0 bottommargin_40\">
                                        {{ page.header.title }}
                                    </h2>
                                    <div class=\"fontsize_20 maxwidth_570 inline-block\">
                                        {{ page.content|raw }}
                                    </div>
                                </div>
                            </div>

                            <div class=\"row\">
                                {% for col in page.header.faqs|batch(4) %}
                                    <div class=\"col-sm-6\">
                                        <div class=\"panel-group\" id=\"accordion{{ loop.index }}\">
                                            {% for item in col %}
                                                <div class=\"panel panel-default\">
                                                    <div class=\"panel-heading\">
                                                        <h4 class=\"panel-title\">
                                                            <a data-toggle=\"collapse\" data-parent=\"#accordion{{ loop.parent.loop.index }}\" href=\"#collapse{{ loop.parent.loop.index }}{{ loop.index }}\" class=\"collapsed\">
                                                                <i class=\"rt-icon2-bubble highlight\"></i>
                                                                {{ item.question }}
                                                            </a>
                                                        </h4>
                                                    </div>
                                                    <div id=\"collapse{{ loop.parent.loop.index }}{{ loop.index }}\" class=\"panel-collapse collapse\">
                                                        <div class=\"panel-body\">
                                                            {{ item.answer }}
                                                        </div>
                                                    </div>
                                                </div>
                                            {% endfor %}
                                        </div>
                                    </div>
                                {% endfor %}
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </article>
    </main>
{% endblock %}
", "faq.html.twig", "C:\\wamp\\www\\web-projects\\moon.agency\\user\\themes\\moon\\templates\\faq.html.twig");
    }
}
