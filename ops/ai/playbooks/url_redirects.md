URL Redirects Workflow:
- Input: ops/ai/context/url_mapping_template.csv
- Architect: confirm permalink structure + redirect strategy (plugin/.htaccess)
- Implementer: import/script redirects; avoid chains
- Tester: spot-check 10% with cURL; confirm 301 & canonical
