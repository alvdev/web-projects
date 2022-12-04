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

/* partials/breadcrumbs.html.twig */
class __TwigTemplate_55b86e92a6465223497d2da6ad3f72467ecaed8506be958a5f404471f0c8d4d9 extends \Twig\Template
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
        $context["crumbs"] = $this->getAttribute(($context["breadcrumbs"] ?? null), "get", [], "method");
        // line 2
        $context["breadcrumbs_config"] = $this->getAttribute($this->getAttribute(($context["config"] ?? null), "plugins", []), "breadcrumbs", []);
        // line 3
        $context["divider"] = $this->getAttribute(($context["breadcrumbs_config"] ?? null), "icon_divider_classes", []);
        // line 4
        echo "
";
        // line 5
        if (((twig_length_filter($this->env, ($context["crumbs"] ?? null)) > 1) || $this->getAttribute(($context["breadcrumbs_config"] ?? null), "show_all", []))) {
            // line 6
            echo "    <section class=\"page_breadcrumbs changeable ls gradient gorizontal_padding section_padding_20 columns_padding_5 table_section\">
        <div class=\"container-fluid\">
            <div class=\"row\">
                <div class=\"col-sm-2 text-center text-sm-left darklinks\">
                    <a href=\"#\">
                        <em>
                            <span class=\"\" data-cfemail=\"533234363d302a13202623233c21277d303c3e\">";
            // line 12
            echo twig_escape_filter($this->env, $this->getAttribute(($context["theme"] ?? null), "email", []));
            echo "</span>
                        </em>
                    </a>
                </div>
                <div class=\"col-sm-8 text-center\">
                    <ol class=\"breadcrumb\">
                        ";
            // line 18
            if ($this->getAttribute(($context["breadcrumbs_config"] ?? null), "icon_home", [])) {
                // line 19
                echo "                            <li>
                                <i class=\"";
                // line 20
                echo twig_escape_filter($this->env, $this->getAttribute(($context["breadcrumbs_config"] ?? null), "icon_home", []), "html", null, true);
                echo "\"></i>
                            </li>
                        ";
            }
            // line 23
            echo "                        ";
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["crumbs"] ?? null));
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
            foreach ($context['_seq'] as $context["_key"] => $context["crumb"]) {
                // line 24
                echo "                            ";
                if ( !$this->getAttribute($context["loop"], "last", [])) {
                    // line 25
                    echo "                                ";
                    if ($this->getAttribute($context["crumb"], "routable", [])) {
                        // line 26
                        echo "                                    <li>
                                        <a itemscope itemtype=\"http://schema.org/Thing\" itemprop=\"item\" href=\"";
                        // line 27
                        echo twig_escape_filter($this->env, $this->getAttribute($context["crumb"], "url", []));
                        echo "\" itemid=\"";
                        echo twig_escape_filter($this->env, $this->getAttribute($context["crumb"], "url", []));
                        echo "\">
                                            <span itemprop=\"name\">";
                        // line 28
                        echo twig_escape_filter($this->env, $this->getAttribute($context["crumb"], "menu", []));
                        echo "</span>
                                        </a>
                                    </li>
                                ";
                    } else {
                        // line 32
                        echo "                                    <li>
                                        <span itemscope itemtype=\"http://schema.org/Thing\" itemprop=\"item\" href=\"";
                        // line 33
                        echo twig_escape_filter($this->env, $this->getAttribute($context["crumb"], "url", []));
                        echo "\" itemid=\"";
                        echo twig_escape_filter($this->env, $this->getAttribute($context["crumb"], "url", []));
                        echo "\">
                                            <span itemprop=\"name\">";
                        // line 34
                        echo twig_escape_filter($this->env, $this->getAttribute($context["crumb"], "menu", []));
                        echo "</span>
                                        </span>
                                    </li>
                                ";
                    }
                    // line 38
                    echo "                                <i class=\"";
                    echo twig_escape_filter($this->env, ($context["divider"] ?? null), "html", null, true);
                    echo "\"></i>
                            ";
                } else {
                    // line 40
                    echo "                                ";
                    if ($this->getAttribute(($context["breadcrumbs_config"] ?? null), "link_trailing", [])) {
                        // line 41
                        echo "                                    <li>
                                        <a itemscope itemtype=\"http://schema.org/Thing\" itemprop=\"item\" href=\"";
                        // line 42
                        echo twig_escape_filter($this->env, $this->getAttribute($context["crumb"], "url", []));
                        echo "\" itemid=\"";
                        echo twig_escape_filter($this->env, $this->getAttribute($context["crumb"], "url", []));
                        echo "\">
                                            <span itemprop=\"name\">";
                        // line 43
                        echo twig_escape_filter($this->env, $this->getAttribute($context["crumb"], "menu", []));
                        echo "</span>
                                        </a>
                                    </li>
                                ";
                    } else {
                        // line 47
                        echo "                                    <li class=\"active\">
                                        <span itemscope itemtype=\"http://schema.org/Thing\" itemprop=\"item\" itemid=\"";
                        // line 48
                        echo twig_escape_filter($this->env, $this->getAttribute($context["crumb"], "url", []));
                        echo "\">
                                            <span itemprop=\"name\">";
                        // line 49
                        echo twig_escape_filter($this->env, $this->getAttribute($context["crumb"], "menu", []));
                        echo "</span>
                                        </span>
                                    </li>
                                ";
                    }
                    // line 53
                    echo "                            ";
                }
                // line 54
                echo "                            <meta itemprop=\"position\" content=\"";
                echo twig_escape_filter($this->env, $this->getAttribute($context["loop"], "index", []), "html", null, true);
                echo "\"/>
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
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['crumb'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 56
            echo "                    </ol>
                </div>
                <div class=\"col-sm-2 text-center text-sm-right\">
                    <ul class=\"inline-dropdown inline-block\">

                        <li class=\"dropdown login-dropdown\">
                            <a class=\"topline-button\" id=\"login\" data-target=\"#\" href=\"index.html\" data-toggle=\"dropdown\" aria-haspopup=\"true\" role=\"button\" aria-expanded=\"false\">
                                <i class=\"rt-icon2-user\"></i>
                            </a>
                            <div class=\"dropdown-menu ds\" aria-labelledby=\"login\">
                                <form role=\"form\" action=\"https://html.modernwebtemplates.com/\">
                                    <div class=\"form-group\">
                                        <label for=\"login_email\" class=\"sr-only\">Email</label>
                                        <input type=\"email\" class=\"form-control\" id=\"login_email\" placeholder=\"";
            // line 69
            echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->translate($this->env, "THEME.ADDRESS"), "html", null, true);
            echo " de email\">
                                    </div>
                                    <div class=\"form-group\">
                                        <label for=\"login_password\" class=\"sr-only\">Contraseña</label>
                                        <input type=\"password\" class=\"form-control\" id=\"login_password\" placeholder=\"Contraseña\">
                                    </div>
                                    <button type=\"button\" class=\"theme_button color1 bottommargin_0\">
                                        Acceder
                                    </button>
                                    <div class=\"checkbox-inline\">
                                        <input type=\"checkbox\" id=\"remember\">
                                        <label for=\"remember\" class=\"bottommargin_0\">
                                            Recordar sesión</label>
                                    </div>
                                </form>
                                <div class=\"topmargin_25\">
                                    <a href=\"register.html\" class=\"text-uppercase\">¿Olvidaste tu contraseña?</a>
                                </div>
                            </div>
                        </li>
                        <li class=\"dropdown\">
                            <a href=\"#\" class=\"search_modal_button topline-button\">
                                <i class=\"rt-icon2-search2\"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
";
        }
    }

    public function getTemplateName()
    {
        return "partials/breadcrumbs.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  199 => 69,  184 => 56,  167 => 54,  164 => 53,  157 => 49,  153 => 48,  150 => 47,  143 => 43,  137 => 42,  134 => 41,  131 => 40,  125 => 38,  118 => 34,  112 => 33,  109 => 32,  102 => 28,  96 => 27,  93 => 26,  90 => 25,  87 => 24,  69 => 23,  63 => 20,  60 => 19,  58 => 18,  49 => 12,  41 => 6,  39 => 5,  36 => 4,  34 => 3,  32 => 2,  30 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("{% set crumbs = breadcrumbs.get() %}
{% set breadcrumbs_config = config.plugins.breadcrumbs %}
{% set divider = breadcrumbs_config.icon_divider_classes %}

{% if crumbs|length > 1 or breadcrumbs_config.show_all %}
    <section class=\"page_breadcrumbs changeable ls gradient gorizontal_padding section_padding_20 columns_padding_5 table_section\">
        <div class=\"container-fluid\">
            <div class=\"row\">
                <div class=\"col-sm-2 text-center text-sm-left darklinks\">
                    <a href=\"#\">
                        <em>
                            <span class=\"\" data-cfemail=\"533234363d302a13202623233c21277d303c3e\">{{ theme.email|e }}</span>
                        </em>
                    </a>
                </div>
                <div class=\"col-sm-8 text-center\">
                    <ol class=\"breadcrumb\">
                        {% if breadcrumbs_config.icon_home %}
                            <li>
                                <i class=\"{{ breadcrumbs_config.icon_home }}\"></i>
                            </li>
                        {% endif %}
                        {% for crumb in crumbs %}
                            {% if not loop.last %}
                                {% if crumb.routable %}
                                    <li>
                                        <a itemscope itemtype=\"http://schema.org/Thing\" itemprop=\"item\" href=\"{{ crumb.url|e }}\" itemid=\"{{ crumb.url|e }}\">
                                            <span itemprop=\"name\">{{ crumb.menu|e }}</span>
                                        </a>
                                    </li>
                                {% else %}
                                    <li>
                                        <span itemscope itemtype=\"http://schema.org/Thing\" itemprop=\"item\" href=\"{{ crumb.url|e }}\" itemid=\"{{ crumb.url|e }}\">
                                            <span itemprop=\"name\">{{ crumb.menu|e }}</span>
                                        </span>
                                    </li>
                                {% endif %}
                                <i class=\"{{ divider }}\"></i>
                            {% else %}
                                {% if breadcrumbs_config.link_trailing %}
                                    <li>
                                        <a itemscope itemtype=\"http://schema.org/Thing\" itemprop=\"item\" href=\"{{ crumb.url|e }}\" itemid=\"{{ crumb.url|e }}\">
                                            <span itemprop=\"name\">{{ crumb.menu|e }}</span>
                                        </a>
                                    </li>
                                {% else %}
                                    <li class=\"active\">
                                        <span itemscope itemtype=\"http://schema.org/Thing\" itemprop=\"item\" itemid=\"{{ crumb.url|e }}\">
                                            <span itemprop=\"name\">{{ crumb.menu|e }}</span>
                                        </span>
                                    </li>
                                {% endif %}
                            {% endif %}
                            <meta itemprop=\"position\" content=\"{{ loop.index }}\"/>
                        {% endfor %}
                    </ol>
                </div>
                <div class=\"col-sm-2 text-center text-sm-right\">
                    <ul class=\"inline-dropdown inline-block\">

                        <li class=\"dropdown login-dropdown\">
                            <a class=\"topline-button\" id=\"login\" data-target=\"#\" href=\"index.html\" data-toggle=\"dropdown\" aria-haspopup=\"true\" role=\"button\" aria-expanded=\"false\">
                                <i class=\"rt-icon2-user\"></i>
                            </a>
                            <div class=\"dropdown-menu ds\" aria-labelledby=\"login\">
                                <form role=\"form\" action=\"https://html.modernwebtemplates.com/\">
                                    <div class=\"form-group\">
                                        <label for=\"login_email\" class=\"sr-only\">Email</label>
                                        <input type=\"email\" class=\"form-control\" id=\"login_email\" placeholder=\"{{ 'THEME.ADDRESS'|t }} de email\">
                                    </div>
                                    <div class=\"form-group\">
                                        <label for=\"login_password\" class=\"sr-only\">Contraseña</label>
                                        <input type=\"password\" class=\"form-control\" id=\"login_password\" placeholder=\"Contraseña\">
                                    </div>
                                    <button type=\"button\" class=\"theme_button color1 bottommargin_0\">
                                        Acceder
                                    </button>
                                    <div class=\"checkbox-inline\">
                                        <input type=\"checkbox\" id=\"remember\">
                                        <label for=\"remember\" class=\"bottommargin_0\">
                                            Recordar sesión</label>
                                    </div>
                                </form>
                                <div class=\"topmargin_25\">
                                    <a href=\"register.html\" class=\"text-uppercase\">¿Olvidaste tu contraseña?</a>
                                </div>
                            </div>
                        </li>
                        <li class=\"dropdown\">
                            <a href=\"#\" class=\"search_modal_button topline-button\">
                                <i class=\"rt-icon2-search2\"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
{% endif %}
", "partials/breadcrumbs.html.twig", "C:\\wamp\\www\\web-projects\\moon.agency\\user\\themes\\moon\\templates\\partials\\breadcrumbs.html.twig");
    }
}
