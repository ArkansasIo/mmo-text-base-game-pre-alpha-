from pathlib import Path
from PIL import Image

root = Path('/home/ubuntu/stargatewars-clone-1.5')
source = Image.open(root / 'images/logo_redesigned.png').convert('RGB')
target_w, target_h = 480, 84
aspect = target_w / target_h
src_w, src_h = source.size
crop_h = int(src_w / aspect)
# Remove the generated image's letterbox while retaining the central logo and ship.
top = max(0, (src_h - crop_h) // 2)
if crop_h > src_h:
    crop_h = src_h
top = max(0, (src_h - crop_h) // 2)
crop = source.crop((0, top, src_w, top + crop_h))
scaled_h = round(target_w * crop.height / crop.width)
scaled = crop.resize((target_w, scaled_h), Image.Resampling.LANCZOS)
canvas = Image.new('RGB', (target_w, target_h), '#030b14')
canvas.paste(scaled, (0, max(0, (target_h - scaled_h) // 2)))
canvas.save(root / 'images/logo.gif', format='GIF', optimize=True)
canvas.save(root / 'images/logo_redesigned_480x84.png', format='PNG', optimize=True)
print('saved', canvas.size)
