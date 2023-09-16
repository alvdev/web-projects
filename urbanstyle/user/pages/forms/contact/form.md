---
title: 'Contact form'
form:
    name: contact
    action: /forms/contact-form
    template: form-messages
    refresh_prevention: true
    fields:
        name:
            label: Name
            placeholder: THEME.FORM_NAME
            autocomplete: 'on'
            type: text
            validate:
                required: true
        email:
            label: Email
            placeholder: THEME.FORM_EMAIL
            type: email
            validate:
                required: true
        message:
            label: Message
            placeholder: THEME.FORM_MESSAGE
            type: textarea
            validate:
                required: true
        basic-captcha:
            label: THEME.CAPTCHA_LABEL
            placeholder: THEME.CAPTCHA_PLACEHOLDER
            type: basic-captcha
    buttons:
        submit:
            type: submit
            value: THEME.FORM_SUBMIT
            classes: 'btn btn--primary'
        reset:
            type: reset
            value: THEME.FORM_RESET
            classes: 'btn btn--default'
    process:
        basic-captcha: true
        save:
            fileprefix: contact-
            dateformat: Ymd-His-u
            extension: txt
            body: '{% include ''forms/data.txt.twig'' %}'
        email:
            subject: '[Site Contact Form] {{ form.value.name|e }}'
            body: '{% include ''forms/data.html.twig'' %}'
        message: THEME.MESSAGE_SENT_SUCCESSFULLY
        remember:
            - name
            - email
        reset: true
cache_enable: false
twitterenable: false
twittercardoptions: summary
orgaenabled: false
orga:
    ratingValue: 2.5
orgaratingenabled: false
facebookenable: false
process:
    markdown: true
    twig: true
---

