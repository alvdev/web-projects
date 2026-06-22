import re

with open('/home/alvdev/dev/www/web-projects/urbanstyle/astro/src/data/cities/allSpainCities.js', 'r') as f:
    content = f.read()

base_url = "https://urbanstylepublicity.com/pegada-carteles/"
urls = [base_url]

# Extract provinces (keys of provincesMetadata)
provinces_match = re.search(r'export const provincesMetadata = \{(.*?)\};', content, re.DOTALL)
if provinces_match:
    provinces_block = provinces_match.group(1)
    # Find keys followed by a colon and an opening brace
    province_slugs = re.findall(r'(\w+):\s*\{', provinces_block)
    for slug in province_slugs:
        urls.append(f"{base_url}{slug}")

# Extract cities from allSpainCities
cities_match = re.search(r'export const allSpainCities = \{(.*?)\};', content, re.DOTALL)
if cities_match:
    cities_block = cities_match.group(1)
    # This regex is a bit fragile for nested objects, let's use a better approach.
    # Looking for province_slug: [ ... ]
    # We can use the fact that it's nicely indented.
    
    # Let's find each province section
    province_matches = re.finditer(r'(\w+):\s*\[', cities_block)
    
    # We'll just collect them all and then filter.
    for m in province_matches:
        p_slug = m.group(1)
        # Find the content until the closing bracket ] followed by a comma or end of block
        # This is tricky with regex. Let's just find all slugs within that province's bracket.
        
        # Start looking from the end of the province match
        start_idx = m.end()
        # Find the corresponding closing bracket for the array
        bracket_count = 1
        end_idx = start_idx
        while bracket_count > 0 and end_idx < len(cities_block):
            if cities_block[end_idx] == '[':
                bracket_count += 1
            elif cities_block[end_idx] == ']':
                bracket_count -= 1
            end_idx += 1
        
        c_block = cities_block[start_idx:end_idx]
        city_slugs = re.findall(r"slug:\s*'([^']+)'", c_block)
        for c_slug in city_slugs:
            if c_slug != p_slug:
                urls.append(f"{base_url}{p_slug}/{c_slug}")

# Remove duplicates and sort
urls = sorted(list(set(urls)))

with open('/home/alvdev/dev/www/web-projects/urbanstyle/astro/urls_pegada_carteles.txt', 'w') as f:
    for url in urls:
        f.write(f"{url}\n")
