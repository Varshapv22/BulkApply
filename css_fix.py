import re

with open('resources/css/app.css', 'r') as f:
    css = f.read()

# 1. Reduce border radii
css = css.replace('border-radius: 18px;', 'border-radius: 8px;')
css = css.replace('border-radius: 24px;', 'border-radius: 12px;')
css = css.replace('border-radius: 20px;', 'border-radius: 12px;')
css = css.replace('border-radius: 14px;', 'border-radius: 8px;')
css = css.replace('border-radius: 13px;', 'border-radius: 8px;')
css = css.replace('border-radius: 12px;', 'border-radius: 6px;')
css = css.replace('border-radius: 11px;', 'border-radius: 6px;')
css = css.replace('border-radius: 10px;', 'border-radius: 6px;')
css = css.replace('border-radius: 9px;', 'border-radius: 4px;')

# 2. Remove hover lifts
css = re.sub(r'(\.stat:hover\s*\{[^}]*)transform:\s*translateY\([^)]+\);?', r'\1', css)
css = re.sub(r'(\.btn-primary:hover\s*\{[^}]*)transform:\s*translateY\([^)]+\);?', r'\1', css)
css = re.sub(r'(\.card:hover\s*\{[^}]*)transform:\s*translateY\([^)]+\);?', r'\1', css)

with open('resources/css/app.css', 'w') as f:
    f.write(css)

print("CSS updated")
