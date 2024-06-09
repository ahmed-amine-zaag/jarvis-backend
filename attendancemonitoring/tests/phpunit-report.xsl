<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:output method="html" indent="yes"/>

  <xsl:template match="/testsuites">
    <html>
      <head>
        <title>PHP Unit Test Report</title>
        <style>
          body { font-family: Arial, sans-serif; }
          table { border-collapse: collapse; width: 100%; }
          th, td { border: 1px solid #ddd; padding: 8px; }
          th { background-color: #f2f2f2; }
        </style>
      </head>
      <body>
        <h2>PHP Unit Test Report</h2>
        <xsl:for-each select="testsuite">
          <h3>Test Suite: <xsl:value-of select="@name"/></h3>
          <table>
            <tr>
              <th>Test Case</th>
              <th>Class</th>
              <th>File</th>
              <th>Line</th>
              <th>Assertions</th>
              <th>Time</th>
            </tr>
            <xsl:for-each select=".//testcase">
              <tr>
                <td><xsl:value-of select="@name"/></td>
                <td><xsl:value-of select="@classname"/></td>
                <td><xsl:value-of select="@file"/></td>
                <td><xsl:value-of select="@line"/></td>
                <td><xsl:value-of select="@assertions"/></td>
                <td><xsl:value-of select="@time"/></td>
              </tr>
            </xsl:for-each>
          </table>
        </xsl:for-each>
      </body>
    </html>
  </xsl:template>
</xsl:stylesheet>
