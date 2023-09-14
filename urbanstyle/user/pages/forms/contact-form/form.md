---
title: 'Contact form'
form:
    name: contact
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
            value: Submit
            classes: 'btn btn--primary'
        reset:
            type: reset
            value: Reset
            classes: 'btn btn--default'
    process:
        basic-captcha:
            message: THEME.CAPTCHA_NOT_VALID
        save:
            fileprefix: contact-
            dateformat: Ymd-His-u
            extension: txt
            body: '{% include ''forms/data.txt.twig'' %}'
        email:
            subject: '[Site Contact Form] {{ form.value.name|e }}'
            body: '{% include ''forms/data.html.twig'' %}'
        message: 'Thank you for getting in touch!'
        display: thankyou
---