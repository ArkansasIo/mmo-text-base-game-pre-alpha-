from pathlib import Path
from PIL import Image, ImageEnhance

ROOT = Path('/home/ubuntu/stargatewars-clone-1.5')
IMG = ROOT / 'images'

sources = {
    'galaxy1.jpg': IMG / 'generated_galaxy_reference.png',
    'galaxy1-2.jpg': IMG / 'generated_galaxy_secondary.png',
    'galaxy2.jpg': IMG / 'generated_galaxy_secondary.png',
    'galaxy2-2.jpg': IMG / 'generated_galaxy_tertiary.png',
    'galaxy3.JPG': IMG / 'generated_galaxy_tertiary.png',
    'galaxy3-2.jpg': IMG / 'generated_galaxy_reference.png',
}

targets = {
    'galaxy1.jpg': (373, 188),
    'galaxy1-2.jpg': (373, 188),
    'galaxy2.jpg': (202, 78),
    'galaxy2-2.jpg': (202, 78),
    'galaxy3.JPG': (366, 126),
    'galaxy3-2.jpg': (366, 126),
}

def crop_to_ratio(image, ratio):
    width, height = image.size
    current = width / height
    if current > ratio:
        new_width = int(height * ratio)
        left = (width - new_width) // 2
        return image.crop((left, 0, left + new_width, height))
    new_height = int(width / ratio)
    top = (height - new_height) // 2
    return image.crop((0, top, width, top + new_height))

for name, source in sources.items():
    target = targets[name]
    with Image.open(source).convert('RGB') as image:
        prepared = crop_to_ratio(image, target[0] / target[1]).resize(target, Image.Resampling.LANCZOS)
        if '-2.' in name:
            prepared = ImageEnhance.Brightness(prepared).enhance(1.16)
            prepared = ImageEnhance.Contrast(prepared).enhance(1.08)
        prepared.save(IMG / name, quality=92, optimize=True)
        print(f'{name}: {source.name} -> {target[0]}x{target[1]}')
