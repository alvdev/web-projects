---
title: Contact
form:
    name: contact-form
    fields:
        - name: name
          label: Name
          placeholder: "Enter your name"
          autofocus: false
          autocomplete: false
          type: text
          validate:
              required: true
        - name: email
          label: Email
          placeholder: "Enter your email address"
          type: email
          validate:
              rule: email
              required: true
        - name: phone
          label: Phone
          placeholder: ""
          type: number
          validate:
              rule: tel
              required: false
        - name: message
          label: Message
          size: long
          placeholder: "Enter your message"
          type: textarea
          validate:
              required: true
    buttons:
        - type: submit
          value: Submit
          classes: btn
    process:
        - email:
              from: "{{ config.plugins.email.from }}"
              to:
                  - "{{ config.plugins.email.from }}"
                  - "{{ form.value.email }}"
              subject: "[Feedback] {{ form.value.name|e }}"
              body: "{% include 'forms/data.html.twig' %}"
        - save:
              fileprefix: feedback-
              dateformat: Ymd-His-u
              extension: txt
              body: "{% include 'forms/data.txt.twig' %}"
        - message: "Thank you for your feedback!"
        - display: thankyou
slug: contact
---

Lorem ipsum dolor sit amet, consectetur adipisicing elit. Praesentium amet ea dicta neque, ut quis omnis quos nam, pariatur, minus, fugit suscipit aspernatur sint ullam quas explicabo. Alias, officiis, dolor!
