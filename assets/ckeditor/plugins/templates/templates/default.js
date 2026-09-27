/*
 Copyright (c) 2003-2013, CKSource - Frederico Knabben. All rights reserved.
 For licensing, see LICENSE.md or http://ckeditor.com/license
*/
CKEDITOR.addTemplates('default', {
    imagesPath: CKEDITOR.getUrl(
        CKEDITOR.plugins.getPath('templates') + 'templates/images/'
    ),
    templates: [
        {
            title: 'Image and Title',
            image: 'template1.gif',
            description: 'A responsive image, heading, and supporting text.',
            html: '<div class="grid items-start gap-6 sm:grid-cols-2">'
                + '<img src="https://placehold.co/1200x800" alt="Describe this image" class="w-full rounded-card object-cover">'
                + '<div><h3>Type the title here</h3><p>Type the text here</p></div>'
                + '</div>',
        },
        {
            title: 'Two-Column Content',
            image: 'template2.gif',
            description: 'Two responsive columns, each with a heading and text.',
            html: '<div class="grid gap-6 sm:grid-cols-2">'
                + '<div><h3>Title 1</h3><p>Text 1</p></div>'
                + '<div><h3>Title 2</h3><p>Text 2</p></div>'
                + '</div>',
        },
        {
            title: 'Text and Table',
            image: 'template3.gif',
            description: 'A heading, supporting text, and responsive content table.',
            html: '<div class="w-full">'
                + '<h3>Title goes here</h3>'
                + '<p>Type the text here</p>'
                + '<table><caption>Table title</caption>'
                + '<thead><tr><th scope="col">Heading 1</th><th scope="col">Heading 2</th><th scope="col">Heading 3</th></tr></thead>'
                + '<tbody><tr><td>Value 1</td><td>Value 2</td><td>Value 3</td></tr></tbody>'
                + '</table></div>',
        },
        {
            title: '1 Image',
            image: 'one-img.gif',
            description: 'One responsive image with the frontend corner radius.',
            html: '<img src="https://placehold.co/1200x800" alt="Describe this image" class="w-full rounded-card object-cover">',
        },
        {
            title: '1 x 2 Images',
            image: 'two-img.gif',
            description: 'Two responsive images in one row on larger screens.',
            html: '<div class="grid gap-4 sm:grid-cols-2">'
                + '<img src="https://placehold.co/1200x800" alt="Describe this image" class="w-full rounded-card object-cover">'
                + '<img src="https://placehold.co/1200x800" alt="Describe this image" class="w-full rounded-card object-cover">'
                + '</div>',
        },
        {
            title: '2 x 2 Images',
            image: 'four-img.gif',
            description: 'Four responsive images in a two-column grid.',
            html: '<div class="grid gap-4 sm:grid-cols-2">'
                + '<img src="https://placehold.co/1200x800" alt="Describe this image" class="w-full rounded-card object-cover">'
                + '<img src="https://placehold.co/1200x800" alt="Describe this image" class="w-full rounded-card object-cover">'
                + '<img src="https://placehold.co/1200x800" alt="Describe this image" class="w-full rounded-card object-cover">'
                + '<img src="https://placehold.co/1200x800" alt="Describe this image" class="w-full rounded-card object-cover">'
                + '</div>',
        },
        {
            title: 'Circle Image',
            image: 'circle-image.gif',
            description: 'A responsive circular image.',
            html: '<img src="https://placehold.co/800x800" alt="Describe this image" class="mx-auto aspect-square w-full max-w-sm rounded-full object-cover">',
        },
        {
            title: 'Rounded Corner Image',
            image: 'rounded-image.gif',
            description: 'A responsive image using the frontend feature radius.',
            html: '<img src="https://placehold.co/1200x800" alt="Describe this image" class="w-full rounded-feature object-cover">',
        },
        {
            title: 'Button',
            description: 'A primary action using the frontend button style.',
            html: '<a href="#" class="btn-primary" style="color:#fff;text-decoration:none">Your Button Text</a>',
        },
        {
            title: 'Section Intro - Left Aligned',
            description: 'Eyebrow label, heading, and supporting paragraph using the frontend section style.',
            html: '<div class="w-full">'
                + '<span class="eyebrow">Title - Eyebrow</span>'
                + '<h2 class="mt-3.5 text-h3">Sample Heading</h2>'
                + '<p>Lorem ipsum dolor sit amet consectetur adipiscing elit maxime at reprehenderit consequatur officia imperdiet id do quos in in sunt veniam anim reprehenderit id optio et voluptate animi minim sunt provident eiusmod nam nam officia iusto sit facilis aliquip dolor qui provident nostrud maxime id mollit optio pariatur et voluptatum</p>'
                + '</div>',
        },
        {
            title: 'Section Intro - Centered',
            description: 'Centered eyebrow label, heading, and supporting paragraph using the frontend section style.',
            html: '<div class="mx-auto max-w-2xl text-center">'
                + '<span class="eyebrow">Title - Eyebrow</span>'
                + '<h2 class="mt-3.5 text-h3">Sample Heading</h2>'
                + '<p>Lorem ipsum dolor sit amet consectetur adipiscing elit maxime at reprehenderit consequatur officia imperdiet id do quos in in sunt veniam anim reprehenderit id optio et voluptate animi minim sunt provident eiusmod nam nam officia iusto sit facilis aliquip dolor qui provident nostrud maxime id mollit optio pariatur et voluptatum</p>'
                + '</div>',
        },
    ],
});
