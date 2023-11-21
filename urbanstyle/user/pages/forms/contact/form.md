---
title: 'Contact form'
form:
    name: contact
    template: form-messages
    refresh_prevention: true
    fields:
        name:
            label: FORM.EMAIL_NAME
            placeholder: THEME.FORM_NAME
            autocomplete: 'on'
            type: text
            validate:
                required: true
        email:
            label: FORM.EMAIL_ACCOUNT
            placeholder: THEME.FORM_EMAIL
            type: email
            validate:
                required: true
        message:
            label: FORM.EMAIL_MESSAGE
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
            classes: 'btn btn--primary rounded-full'
        reset:
            type: reset
            value: THEME.FORM_RESET
            classes: 'btn btn--default rounded-full'
    process:
        basic-captcha: true
        save:
            fileprefix: contact-
            dateformat: dmY-His-u
            extension: txt
            body: "{% include 'forms/data.txt.twig' %}"
        email:
            subject: FORM.EMAIL_SUBJECT
            body: "{% include 'forms/data.html.twig' %}"
            reply_to: "{{ form.value.name|e }} <{{ form.value.email|e }}>"
        message: THEME.MESSAGE_SENT_SUCCESSFULLY
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
