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

/* partials/_models.html.twig */
class __TwigTemplate_1b292985149dcfae590cc394ef52813f0f7af2f29a15344d3cf96973ca998434 extends \Twig\Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        // line 1
        $context["options"] = ["items" => ["@page.children" => "/models"]];
        // line 2
        $context["models"] = $this->getAttribute(($context["page"] ?? null), "collection", [0 => ($context["options"] ?? null)], "method");
        // line 3
        echo "
";
        // line 7
        echo "
<section class=\"ds ms page_models models_portrait gorizontal_padding section_padding_70\">
    <div class=\"container-fluid\">
        <div class=\"isotope_container isotope row masonry-layout\" data-filters=\".isotope_filters\">

            ";
        // line 12
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["models"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["model"]) {
            // line 13
            echo "                <div class=\"isotope-item col-lg-3 col-md-4 col-sm-6 fashion\">
                    <div class=\"vertical-item content-absolute\">
                        <div class=\"item-media\">
                            <a href=\"";
            // line 16
            echo twig_escape_filter($this->env, $this->getAttribute($context["model"], "url", []), "html", null, true);
            echo "\">
                                ";
            // line 17
            if (twig_first($this->env, $this->getAttribute($this->getAttribute($context["model"], "media", []), "images", []))) {
                // line 18
                echo "                                    ";
                echo $this->getAttribute($this->getAttribute(twig_first($this->env, $this->getAttribute($this->getAttribute($context["model"], "media", []), "images", [])), "cropZoom", [0 => 338, 1 => 451], "method"), "html", [0 => "", 1 => "", 2 => "attachment-receptar-featured size-receptar-featured wp-post-image"], "method");
                echo "
                                ";
            } else {
                // line 20
                echo "                                    <img class=\"attachment-receptar-featured size-receptar-featured wp-post-image\" src=\"";
                echo twig_escape_filter($this->env, ($context["theme_url"] ?? null), "html", null, true);
                echo "/images/";
                echo twig_escape_filter($this->env, $this->getAttribute(($context["site"] ?? null), "global_featured_image", []), "html", null, true);
                echo "\">
                                ";
            }
            // line 22
            echo "                                <div class=\"media-links\">
                                    <div class=\"links-wrap model-parameters\">
                                        <div>
                                            <span class=\"bold\">";
            // line 25
            echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->translate($this->env, "PROFILE.HEIGHT"), "html", null, true);
            echo "</span>
                                            <span>
                                                ";
            // line 27
            if ($this->getAttribute($this->getAttribute($context["model"], "header", []), "height", [])) {
                // line 28
                echo "                                                    ";
                echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["model"], "header", []), "height", []), "html", null, true);
                echo "
                                                    cm
                                                ";
            } else {
                // line 31
                echo "                                                    ";
                echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->translate($this->env, "NOT_DEFINED"), "html", null, true);
                echo "
                                                ";
            }
            // line 33
            echo "                                            </span>
                                        </div>
                                        <div>
                                            <span class=\"bold\">";
            // line 36
            echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->translate($this->env, "PROFILE.BUST"), "html", null, true);
            echo "</span>
                                            <span>
                                                ";
            // line 38
            if ($this->getAttribute($this->getAttribute($context["model"], "header", []), "bust", [])) {
                // line 39
                echo "                                                    ";
                echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["model"], "header", []), "bust", []), "html", null, true);
                echo "
                                                    cm
                                                ";
            } else {
                // line 42
                echo "                                                    ";
                echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->translate($this->env, "NOT_DEFINED"), "html", null, true);
                echo "
                                                ";
            }
            // line 44
            echo "                                            </span>
                                        </div>
                                        <div>
                                            <span class=\"bold\">";
            // line 47
            echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->translate($this->env, "PROFILE.WAIST"), "html", null, true);
            echo "</span>
                                            <span>
                                                ";
            // line 49
            if ($this->getAttribute($this->getAttribute($context["model"], "header", []), "waist", [])) {
                // line 50
                echo "                                                    ";
                echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["model"], "header", []), "waist", []), "html", null, true);
                echo "
                                                    cm
                                                ";
            } else {
                // line 53
                echo "                                                    ";
                echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->translate($this->env, "NOT_DEFINED"), "html", null, true);
                echo "
                                                ";
            }
            // line 55
            echo "                                            </span>
                                        </div>
                                        <div>
                                            <span class=\"bold\">";
            // line 58
            echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->translate($this->env, "PROFILE.HIPS"), "html", null, true);
            echo "</span>
                                            <span>
                                                ";
            // line 60
            if ($this->getAttribute($this->getAttribute($context["model"], "header", []), "hips", [])) {
                // line 61
                echo "                                                    ";
                echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["model"], "header", []), "hips", []), "html", null, true);
                echo "
                                                    cm
                                                ";
            } else {
                // line 64
                echo "                                                    ";
                echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->translate($this->env, "NOT_DEFINED"), "html", null, true);
                echo "
                                                ";
            }
            // line 66
            echo "                                            </span>
                                        </div>
                                        <div>
                                            <span class=\"bold\">";
            // line 69
            echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->translate($this->env, "PROFILE.AGE"), "html", null, true);
            echo "</span>
                                            <span>
                                                ";
            // line 71
            if ($this->getAttribute($this->getAttribute($context["model"], "header", []), "age", [])) {
                // line 72
                echo "                                                    ";
                echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["model"], "header", []), "age", []), "html", null, true);
                echo "
                                                    ";
                // line 73
                echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->translate($this->env, "PROFILE.YEARS"), "html", null, true);
                echo "
                                                ";
            } else {
                // line 75
                echo "                                                    ";
                echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->translate($this->env, "NOT_DEFINED"), "html", null, true);
                echo "
                                                ";
            }
            // line 77
            echo "                                            </span>
                                        </div>
                                        <div>
                                            <span class=\"bold\">";
            // line 80
            echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->translate($this->env, "PROFILE.EYES"), "html", null, true);
            echo "</span>
                                            <span>
                                                ";
            // line 82
            if ($this->getAttribute($this->getAttribute($context["model"], "header", []), "eyes", [])) {
                // line 83
                echo "                                                    ";
                echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->translate($this->env, $this->getAttribute($this->getAttribute($context["model"], "header", []), "eyes", [])), "html", null, true);
                echo "
                                                ";
            } else {
                // line 85
                echo "                                                    ";
                echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->translate($this->env, "NOT_DEFINED"), "html", null, true);
                echo "
                                                ";
            }
            // line 87
            echo "                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                    <div class=\"item-title text-center\">
                        <h4>";
            // line 95
            echo twig_escape_filter($this->env, $this->getAttribute($context["model"], "title", []), "html", null, true);
            echo "</h4>
                    </div>
                </div>
            ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['model'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 99
        echo "        </div>
    </div>
</section>
";
    }

    public function getTemplateName()
    {
        return "partials/_models.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  245 => 99,  235 => 95,  225 => 87,  219 => 85,  213 => 83,  211 => 82,  206 => 80,  201 => 77,  195 => 75,  190 => 73,  185 => 72,  183 => 71,  178 => 69,  173 => 66,  167 => 64,  160 => 61,  158 => 60,  153 => 58,  148 => 55,  142 => 53,  135 => 50,  133 => 49,  128 => 47,  123 => 44,  117 => 42,  110 => 39,  108 => 38,  103 => 36,  98 => 33,  92 => 31,  85 => 28,  83 => 27,  78 => 25,  73 => 22,  65 => 20,  59 => 18,  57 => 17,  53 => 16,  48 => 13,  44 => 12,  37 => 7,  34 => 3,  32 => 2,  30 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("{% set options = {items: {'@page.children': '/models'}} %}
{% set models = page.collection(options) %}

{# {% for item in models %}
    {{ unite_gallery(item.media.images)|raw }}
{% endfor %} #}

<section class=\"ds ms page_models models_portrait gorizontal_padding section_padding_70\">
    <div class=\"container-fluid\">
        <div class=\"isotope_container isotope row masonry-layout\" data-filters=\".isotope_filters\">

            {% for model in models %}
                <div class=\"isotope-item col-lg-3 col-md-4 col-sm-6 fashion\">
                    <div class=\"vertical-item content-absolute\">
                        <div class=\"item-media\">
                            <a href=\"{{ model.url }}\">
                                {% if model.media.images|first %}
                                    {{ model.media.images|first.cropZoom(338, 451).html('', '', 'attachment-receptar-featured size-receptar-featured wp-post-image')|raw }}
                                {% else %}
                                    <img class=\"attachment-receptar-featured size-receptar-featured wp-post-image\" src=\"{{ theme_url }}/images/{{ site.global_featured_image }}\">
                                {% endif %}
                                <div class=\"media-links\">
                                    <div class=\"links-wrap model-parameters\">
                                        <div>
                                            <span class=\"bold\">{{ 'PROFILE.HEIGHT'|t }}</span>
                                            <span>
                                                {% if model.header.height %}
                                                    {{ model.header.height }}
                                                    cm
                                                {% else %}
                                                    {{ 'NOT_DEFINED'|t }}
                                                {% endif %}
                                            </span>
                                        </div>
                                        <div>
                                            <span class=\"bold\">{{ 'PROFILE.BUST'|t }}</span>
                                            <span>
                                                {% if model.header.bust %}
                                                    {{ model.header.bust }}
                                                    cm
                                                {% else %}
                                                    {{ 'NOT_DEFINED'|t }}
                                                {% endif %}
                                            </span>
                                        </div>
                                        <div>
                                            <span class=\"bold\">{{ 'PROFILE.WAIST'|t }}</span>
                                            <span>
                                                {% if model.header.waist %}
                                                    {{ model.header.waist }}
                                                    cm
                                                {% else %}
                                                    {{ 'NOT_DEFINED'|t }}
                                                {% endif %}
                                            </span>
                                        </div>
                                        <div>
                                            <span class=\"bold\">{{ 'PROFILE.HIPS'|t }}</span>
                                            <span>
                                                {% if model.header.hips %}
                                                    {{ model.header.hips }}
                                                    cm
                                                {% else %}
                                                    {{ 'NOT_DEFINED'|t }}
                                                {% endif %}
                                            </span>
                                        </div>
                                        <div>
                                            <span class=\"bold\">{{ 'PROFILE.AGE'|t }}</span>
                                            <span>
                                                {% if model.header.age %}
                                                    {{ model.header.age }}
                                                    {{ 'PROFILE.YEARS'|t }}
                                                {% else %}
                                                    {{ 'NOT_DEFINED'|t }}
                                                {% endif %}
                                            </span>
                                        </div>
                                        <div>
                                            <span class=\"bold\">{{ 'PROFILE.EYES'|t }}</span>
                                            <span>
                                                {% if model.header.eyes %}
                                                    {{ model.header.eyes|t }}
                                                {% else %}
                                                    {{ 'NOT_DEFINED'|t }}
                                                {% endif %}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                    <div class=\"item-title text-center\">
                        <h4>{{ model.title }}</h4>
                    </div>
                </div>
            {% endfor %}
        </div>
    </div>
</section>
", "partials/_models.html.twig", "C:\\wamp\\www\\web-projects\\moon.agency\\user\\themes\\moon\\templates\\partials\\_models.html.twig");
    }
}
