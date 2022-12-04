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

/* partials/footer.html.twig */
class __TwigTemplate_e90f2090da626fd21eefdc80addaef2b4bf3b6d65acb6e277d4d2c0b5a77e402 extends \Twig\Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
            'links' => [$this, 'block_links'],
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        // line 1
        echo "<footer class=\"ds page_footer section_padding_70\">
    <div class=\"container\">
        <div class=\"row\">
            <div class=\"col-md-3 col-lg-3 to_animate\" data-animation=\"scaleAppear\">
                <div class=\"widget widget_text\">
                    <a href=\"index.html\" class=\"logo logo_image bottommargin_10\">
                        <img src=\"";
        // line 7
        echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->urlFunc("theme://images/moon-logo.webp"), "html", null, true);
        echo "\" alt=\"\"/>
                    </a>
                    <p>
                        ";
        // line 10
        echo twig_escape_filter($this->env, $this->getAttribute(($context["theme"] ?? null), "slogan", []), "html", null, true);
        echo "
                    </p>
                    <div class=\"size_small\">
                        <i class=\"fa fa-cc-visa\"></i>
                        <i class=\"fa fa-cc-discover\"></i>
                        <i class=\"fa fa-cc-mastercard\"></i>
                        <i class=\"fa fa-cc-amex\"></i>
                        <i class=\"fa fa-cc-paypal\"></i>
                    </div>
                </div>
            </div>
            <div class=\"col-md-6 col-lg-offset-1 col-lg-4 to_animate\" data-animation=\"scaleAppear\">
                <div class=\"row footer_lists\">
                    <div class=\"col-xs-6\">
                        <h3 class=\"widget-title\">Navegación</h3>
                        <div>
                            <ul class=\"list2 bottommargin_0\">
                                <li class=\"\">
                                    <a href=\"index.html\">Presentación</a>
                                </li>
                                <li class=\"\">
                                    <a href=\"about.html\">Modelos</a>
                                </li>

                                <li class=\"\">
                                    <a href=\"contact.html\">Preguntas y respuestas</a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class=\"col-xs-6\">
                        <h3 class=\"widget-title\">Enlaces</h3>
                        ";
        // line 43
        $this->displayBlock('links', $context, $blocks);
        // line 46
        echo "                    </div>
                </div>
            </div>
            <div class=\"col-md-3 col-lg-offset-1 col-lg-3 to_animate\" data-animation=\"scaleAppear\">
                <div class=\"widget widget_banner\">
                    <div class=\"vertical-item content-absolute ds\">
                        <div class=\"item-media\">
                            <img src=\"";
        // line 53
        echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->urlFunc("theme://images/models_square/01.jpg"), "html", null, true);
        echo "\" alt=\"\"/>
                        </div>
                        <div class=\"item-content\">
                            <span class=\"main_bg_color\">
                                Descarga
                            </span>
                            <h2>
                                Nuevo
                                <br/>
                                Catálogo
                            </h2>
                        </div>
                        <div class=\"media-links\">
                            <a href=\"#\" class=\"abs-link\"></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
";
    }

    // line 43
    public function block_links($context, array $blocks = [])
    {
        // line 44
        echo "                            ";
        $this->loadTemplate("partials/legal-links.html.twig", "partials/footer.html.twig", 44)->display($context);
        // line 45
        echo "                        ";
    }

    public function getTemplateName()
    {
        return "partials/footer.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  124 => 45,  121 => 44,  118 => 43,  92 => 53,  83 => 46,  81 => 43,  45 => 10,  39 => 7,  31 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("<footer class=\"ds page_footer section_padding_70\">
    <div class=\"container\">
        <div class=\"row\">
            <div class=\"col-md-3 col-lg-3 to_animate\" data-animation=\"scaleAppear\">
                <div class=\"widget widget_text\">
                    <a href=\"index.html\" class=\"logo logo_image bottommargin_10\">
                        <img src=\"{{ url('theme://images/moon-logo.webp') }}\" alt=\"\"/>
                    </a>
                    <p>
                        {{ theme.slogan }}
                    </p>
                    <div class=\"size_small\">
                        <i class=\"fa fa-cc-visa\"></i>
                        <i class=\"fa fa-cc-discover\"></i>
                        <i class=\"fa fa-cc-mastercard\"></i>
                        <i class=\"fa fa-cc-amex\"></i>
                        <i class=\"fa fa-cc-paypal\"></i>
                    </div>
                </div>
            </div>
            <div class=\"col-md-6 col-lg-offset-1 col-lg-4 to_animate\" data-animation=\"scaleAppear\">
                <div class=\"row footer_lists\">
                    <div class=\"col-xs-6\">
                        <h3 class=\"widget-title\">Navegación</h3>
                        <div>
                            <ul class=\"list2 bottommargin_0\">
                                <li class=\"\">
                                    <a href=\"index.html\">Presentación</a>
                                </li>
                                <li class=\"\">
                                    <a href=\"about.html\">Modelos</a>
                                </li>

                                <li class=\"\">
                                    <a href=\"contact.html\">Preguntas y respuestas</a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class=\"col-xs-6\">
                        <h3 class=\"widget-title\">Enlaces</h3>
                        {% block links %}
                            {% include \"partials/legal-links.html.twig\" %}
                        {% endblock %}
                    </div>
                </div>
            </div>
            <div class=\"col-md-3 col-lg-offset-1 col-lg-3 to_animate\" data-animation=\"scaleAppear\">
                <div class=\"widget widget_banner\">
                    <div class=\"vertical-item content-absolute ds\">
                        <div class=\"item-media\">
                            <img src=\"{{ url('theme://images/models_square/01.jpg') }}\" alt=\"\"/>
                        </div>
                        <div class=\"item-content\">
                            <span class=\"main_bg_color\">
                                Descarga
                            </span>
                            <h2>
                                Nuevo
                                <br/>
                                Catálogo
                            </h2>
                        </div>
                        <div class=\"media-links\">
                            <a href=\"#\" class=\"abs-link\"></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
", "partials/footer.html.twig", "C:\\wamp\\www\\web-projects\\moon.agency\\user\\themes\\moon\\templates\\partials\\footer.html.twig");
    }
}
