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

/* partials/_about-us.html.twig */
class __TwigTemplate_5b719cde1fa8e148263a87ec8a206db7c8be392adf53f112b45ebbec811aab93 extends \Twig\Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
            'content' => [$this, 'block_content'],
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        // line 1
        $this->displayBlock('content', $context, $blocks);
    }

    public function block_content($context, array $blocks = [])
    {
        // line 2
        echo "<section class=\"ds intro_section\">
    <div class=\"flexslider vertical-nav\">
        <ul class=\"slides\">
            <li>
                <img src=\"";
        // line 6
        echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->urlFunc("theme://images/slide01.png"), "html", null, true);
        echo "\" alt=\"\"/>
                <div class=\"container\">
                    <div class=\"row\">
                        <div class=\"col-sm-12 text-center text-md-right\">
                            <div class=\"slide_description_wrapper\">
                                <div class=\"slide_description\">
                                    <div class=\"intro-layer\" data-animation=\"slideExpandUp\">
                                        <h2 class=\"big margin_0\">Sobre la agencia</h2>
                                        <h2 class=\"muellerhoff topmargin_5 bottommargin_50 highlight\">
                                            Lo que nos define
                                        </h2>
                                    </div>
                                    <div class=\"intro-layer\" data-animation=\"slideExpandUp\">
                                        ";
        // line 19
        echo $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->markdownFunction($context, $this->getAttribute(($context["theme"] ?? null), "about_us", []));
        echo "
                                    </div>
                                </div>
                                <!-- eof .slide_description -->
                            </div>
                            <!-- eof .slide_description_wrapper -->
                        </div>
                        <!-- eof .col-* -->
                    </div>
                    <!-- eof .row -->
                </div>
                <!-- eof .container -->
            </li>

            <li>
                <img src=\"";
        // line 34
        echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->urlFunc("theme://images/slide02.png"), "html", null, true);
        echo "\" alt=\"\"/>
                <div class=\"container\">
                    <div class=\"row\">
                        <div class=\"col-sm-12 text-center text-md-right\">
                            <div class=\"slide_description_wrapper\">
                                <div class=\"slide_description\">
                                    <div class=\"intro-layer\" data-animation=\"slideExpandUp\">
                                        <h2 class=\"big margin_0\">Nuestros servicios</h2>
                                        <h2 class=\"muellerhoff topmargin_5 bottommargin_50 highlight\">
                                            Lo que hacemos
                                        </h2>
                                    </div>
                                    <div class=\"intro-layer\" data-animation=\"slideExpandUp\">
                                        ";
        // line 47
        echo $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->markdownFunction($context, $this->getAttribute(($context["theme"] ?? null), "our_services", []));
        echo "
                                    </div>
                                </div>
                                <!-- eof .slide_description -->
                            </div>
                            <!-- eof .slide_description_wrapper -->
                        </div>
                        <!-- eof .col-* -->
                    </div>
                    <!-- eof .row -->
                </div>
                <!-- eof .container -->
            </li>
        </ul>
    </div>
    <!-- eof flexslider -->
</section>
";
    }

    public function getTemplateName()
    {
        return "partials/_about-us.html.twig";
    }

    public function getDebugInfo()
    {
        return array (  93 => 47,  77 => 34,  59 => 19,  43 => 6,  37 => 2,  31 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("{% block content %}
<section class=\"ds intro_section\">
    <div class=\"flexslider vertical-nav\">
        <ul class=\"slides\">
            <li>
                <img src=\"{{ url('theme://images/slide01.png') }}\" alt=\"\"/>
                <div class=\"container\">
                    <div class=\"row\">
                        <div class=\"col-sm-12 text-center text-md-right\">
                            <div class=\"slide_description_wrapper\">
                                <div class=\"slide_description\">
                                    <div class=\"intro-layer\" data-animation=\"slideExpandUp\">
                                        <h2 class=\"big margin_0\">Sobre la agencia</h2>
                                        <h2 class=\"muellerhoff topmargin_5 bottommargin_50 highlight\">
                                            Lo que nos define
                                        </h2>
                                    </div>
                                    <div class=\"intro-layer\" data-animation=\"slideExpandUp\">
                                        {{ theme.about_us|markdown }}
                                    </div>
                                </div>
                                <!-- eof .slide_description -->
                            </div>
                            <!-- eof .slide_description_wrapper -->
                        </div>
                        <!-- eof .col-* -->
                    </div>
                    <!-- eof .row -->
                </div>
                <!-- eof .container -->
            </li>

            <li>
                <img src=\"{{ url('theme://images/slide02.png') }}\" alt=\"\"/>
                <div class=\"container\">
                    <div class=\"row\">
                        <div class=\"col-sm-12 text-center text-md-right\">
                            <div class=\"slide_description_wrapper\">
                                <div class=\"slide_description\">
                                    <div class=\"intro-layer\" data-animation=\"slideExpandUp\">
                                        <h2 class=\"big margin_0\">Nuestros servicios</h2>
                                        <h2 class=\"muellerhoff topmargin_5 bottommargin_50 highlight\">
                                            Lo que hacemos
                                        </h2>
                                    </div>
                                    <div class=\"intro-layer\" data-animation=\"slideExpandUp\">
                                        {{ theme.our_services|markdown }}
                                    </div>
                                </div>
                                <!-- eof .slide_description -->
                            </div>
                            <!-- eof .slide_description_wrapper -->
                        </div>
                        <!-- eof .col-* -->
                    </div>
                    <!-- eof .row -->
                </div>
                <!-- eof .container -->
            </li>
        </ul>
    </div>
    <!-- eof flexslider -->
</section>
{% endblock %}
", "partials/_about-us.html.twig", "C:\\wamp\\www\\web-projects\\moon.agency\\user\\themes\\moon\\templates\\partials\\_about-us.html.twig");
    }
}
