<?xml version="1.0" encoding="UTF-8"?>
<!--
    Browser-only presentation for sitemap.xml. Search engines ignore this file
    and read the XML directly; it only makes the sitemap readable for people.
-->
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:s="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:xhtml="http://www.w3.org/1999/xhtml"
    xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
    exclude-result-prefixes="s xhtml image">

    <xsl:output method="html" encoding="UTF-8" indent="yes" doctype-system="about:legacy-compat"/>

    <xsl:template match="/">
        <html lang="en">
            <head>
                <meta charset="utf-8"/>
                <meta name="viewport" content="width=device-width, initial-scale=1"/>
                <meta name="robots" content="noindex"/>
                <title>XML Sitemap</title>
                <style>
                    :root {
                        --brand: #63569b;
                        --brand-dark: #443a70;
                        --ink: #1f2430;
                        --muted: #6b7280;
                        --line: #e5e7eb;
                        --surface: #f7f6fb;
                    }

                    * { box-sizing: border-box; }

                    body {
                        margin: 0;
                        font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
                        color: var(--ink);
                        background: var(--surface);
                        line-height: 1.5;
                    }

                    header {
                        background: linear-gradient(135deg, var(--brand-dark), var(--brand));
                        color: #fff;
                        padding: 40px 24px 56px;
                    }

                    .wrap { max-width: 1100px; margin: 0 auto; }

                    h1 { margin: 0 0 8px; font-size: 28px; }

                    header p { margin: 0; max-width: 640px; opacity: .85; }

                    .stats { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 24px; }

                    .stat {
                        background: rgba(255, 255, 255, .14);
                        border-radius: 10px;
                        padding: 10px 16px;
                        font-size: 13px;
                    }

                    .stat strong { display: block; font-size: 22px; }

                    main { padding: 0 24px 48px; margin-top: -28px; }

                    .card {
                        background: #fff;
                        border-radius: 14px;
                        box-shadow: 0 8px 30px rgba(31, 36, 48, .08);
                        overflow-x: auto;
                    }

                    table { width: 100%; border-collapse: collapse; font-size: 14px; }

                    th {
                        text-align: left;
                        padding: 14px 16px;
                        font-size: 12px;
                        letter-spacing: .04em;
                        text-transform: uppercase;
                        color: var(--muted);
                        border-bottom: 1px solid var(--line);
                        white-space: nowrap;
                    }

                    td { padding: 12px 16px; border-bottom: 1px solid var(--line); vertical-align: top; }

                    tr:last-child td { border-bottom: 0; }

                    tbody tr:hover { background: var(--surface); }

                    td.url { word-break: break-all; }

                    a { color: var(--brand); text-decoration: none; }

                    a:hover { text-decoration: underline; }

                    .badge {
                        display: inline-block;
                        padding: 2px 10px;
                        border-radius: 999px;
                        font-size: 12px;
                        font-weight: 600;
                        background: #ece9f7;
                        color: var(--brand-dark);
                        white-space: nowrap;
                    }

                    .badge.lang { background: #e6f4ea; color: #1e6b3a; }

                    .muted { color: var(--muted); white-space: nowrap; }

                    footer { text-align: center; color: var(--muted); font-size: 13px; padding: 0 24px 32px; }
                </style>
            </head>
            <body>
                <header>
                    <div class="wrap">
                        <h1>XML Sitemap</h1>
                        <p>
                            Every public page of this site, in both languages, as search engines
                            see it. Each row shows a page's type, language and last modified date.
                        </p>
                        <div class="stats">
                            <div class="stat">
                                <strong><xsl:value-of select="count(s:urlset/s:url)"/></strong>
                                URLs
                            </div>
                            <div class="stat">
                                <strong><xsl:value-of select="count(s:urlset/s:url[not(xhtml:link[@hreflang = 'ar-SA']/@href = s:loc)])"/></strong>
                                English
                            </div>
                            <div class="stat">
                                <strong><xsl:value-of select="count(s:urlset/s:url[xhtml:link[@hreflang = 'ar-SA']/@href = s:loc])"/></strong>
                                Arabic
                            </div>
                        </div>
                    </div>
                </header>

                <main>
                    <div class="wrap card">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>URL</th>
                                    <th>Type</th>
                                    <th>Language</th>
                                    <th>Last modified</th>
                                </tr>
                            </thead>
                            <tbody>
                                <xsl:for-each select="s:urlset/s:url">
                                    <xsl:variable name="loc" select="s:loc"/>
                                    <tr>
                                        <td class="muted"><xsl:value-of select="position()"/></td>
                                        <td class="url">
                                            <a href="{$loc}"><xsl:value-of select="$loc"/></a>
                                        </td>
                                        <td>
                                            <span class="badge">
                                                <xsl:choose>
                                                    <xsl:when test="contains($loc, '/experience/')">Experience</xsl:when>
                                                    <xsl:when test="contains($loc, '/tour/')">Tour</xsl:when>
                                                    <xsl:when test="contains($loc, '/blog/category/')">Blog category</xsl:when>
                                                    <xsl:when test="contains($loc, '/blog/')">Blog post</xsl:when>
                                                    <xsl:otherwise>Page</xsl:otherwise>
                                                </xsl:choose>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge lang">
                                                <xsl:choose>
                                                    <xsl:when test="xhtml:link[@href = $loc and @hreflang = 'ar-SA']">العربية</xsl:when>
                                                    <xsl:otherwise>English</xsl:otherwise>
                                                </xsl:choose>
                                            </span>
                                        </td>
                                        <td class="muted">
                                            <xsl:choose>
                                                <xsl:when test="s:lastmod"><xsl:value-of select="substring(s:lastmod, 1, 10)"/></xsl:when>
                                                <xsl:otherwise>&#8212;</xsl:otherwise>
                                            </xsl:choose>
                                        </td>
                                    </tr>
                                </xsl:for-each>
                            </tbody>
                        </table>
                    </div>
                </main>

                <footer>Generated from the live site content. Crawlers read the raw XML.</footer>
            </body>
        </html>
    </xsl:template>
</xsl:stylesheet>
