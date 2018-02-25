---
title: Ropa
media_order: 'ofertas-notext.jpg,ropa-mujer.jpg,temporada.jpg'
links:
    temporada:
        enabled: '1'
        text: Temporada
        img: temporada.jpg
    oferta:
        enabled: '1'
        text: Ofertas
        img: ofertas-notext.jpg
    blog:
        enabled: '1'
        text: 'Blog Estilo'
        img: ropa-mujer.jpg
menuextras:
    -
        img: temporada-verano.jpg
    -
        img: ofertas.jpg
    -
        img: estilo.jpg
content:
    items: '@self.children'
    order:
        by: title
        dir: asc
---

