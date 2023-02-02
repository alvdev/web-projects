---
title: 'Blog'
blog_url: blog
sitemap:
    changefreq: monthly
    priority: 1.03
content:
    items: '@self.children'
    order:
        by: date
        dir: desc
    limit: 6
    pagination: true
feed:
    description: ''
    limit: 10
pagination: true
---
