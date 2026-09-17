#!/usr/bin/env python3
"""
Générateur d'illustrations féodales pour les unités de l'Atelier de Siège & des Écuries
Crée des visuels 1024x1024 stylisés dans la charte graphique d'OpenShogun (style Travian / Shogun)
avec teintes de papier washi, emblèmes de clans (Kamon), compositions martiales et sceaux kanji.
"""

import os
import math
from PIL import Image, ImageDraw, ImageFont, ImageFilter, ImageEnhance

UNITS_DIR = "/var/www/opengalaxy/public/assets/units"
ASSETS_DIR = "/var/www/opengalaxy/public/assets"
os.makedirs(UNITS_DIR, exist_ok=True)

# 13 unités de siège, cavalerie et convois
UNITS_CONFIG = [
    # --- CLAN ODA ---
    {
        "code": "terran_interceptor",
        "alias": "cavalier_interception_oda",
        "name": "Cavalier Léger d'Interception",
        "kanji": "騎兵",
        "clan": "oda",
        "clan_color": (59, 130, 246),      # Bleu Oda
        "accent": (245, 158, 11),
        "source": "cavalier_interception_oda.jpg", # Déjà généré par IA !
        "type": "cavalry",
        "desc": "Cavalier rapide d'Owari en patrouille frontalière"
    },
    {
        "code": "terran_cruiser",
        "alias": "belier_acier_oda",
        "name": "Bélier Blindé à Éperon d'Acier",
        "kanji": "破城槌",
        "clan": "oda",
        "clan_color": (59, 130, 246),
        "accent": (239, 68, 68),
        "type": "ram",
        "desc": "Engin de siège lourd renforcé pour fracasser les portes fortifiées"
    },
    {
        "code": "terran_dreadnought",
        "alias": "tour_siege_baliste_oda",
        "name": "Grande Tour de Siège & Baliste",
        "kanji": "攻城塔",
        "clan": "oda",
        "clan_color": (59, 130, 246),
        "accent": (234, 179, 8),
        "type": "tower",
        "desc": "Machine de guerre monumentale pilonnant les forteresses ennemies"
    },

    # --- CLAN TAKEDA ---
    {
        "code": "vorash_drone",
        "alias": "cavalier_eclaireur_takeda",
        "name": "Cavalier Éclaireur Takeda",
        "kanji": "疾風",
        "clan": "takeda",
        "clan_color": (239, 68, 68),      # Rouge vermillon Takeda
        "accent": (251, 191, 36),
        "type": "cavalry_light",
        "desc": "Cavalier agile d'assaut menant les raids fulgurants de Kai"
    },
    {
        "code": "vorash_manticore",
        "alias": "cavalerie_cuirassee_takeda",
        "name": "Escadron de Cavalerie Cuirassée",
        "kanji": "赤備え",
        "clan": "takeda",
        "clan_color": (220, 38, 38),
        "accent": (254, 240, 138),
        "source": "cavalier_rouge_akazonae.jpg",
        "type": "cavalry_heavy",
        "desc": "Fer de lance de la légendaire Cavalerie Rouge Akazonae de Takeda"
    },
    {
        "code": "vorash_leviathan",
        "alias": "belier_dragon_kai",
        "name": "Bélier Titanesque du Dragon de Kai",
        "kanji": "甲斐龍",
        "clan": "takeda",
        "clan_color": (185, 28, 28),
        "accent": (249, 115, 22),
        "type": "dragon_ram",
        "desc": "Engin de guerre colossal orné d'une tête de dragon écrasant les murailles"
    },

    # --- CLAN TOKUGAWA ---
    {
        "code": "aethelis_mirage",
        "alias": "shinobi_monte_tokugawa",
        "name": "Embuscade Shinobi Montée",
        "kanji": "忍騎",
        "clan": "tokugawa",
        "clan_color": (139, 92, 246),    # Violet / Indigo Tokugawa
        "accent": (56, 189, 248),
        "type": "shinobi_mount",
        "desc": "Troupe furtive Tokugawa expertisant le terrain et prenant l'ennemi à revers"
    },
    {
        "code": "aethelis_prism",
        "alias": "catapulte_horokubiya_tokugawa",
        "name": "Catapulte Flamboyante Horokubiya",
        "kanji": "焙烙火矢",
        "clan": "tokugawa",
        "clan_color": (124, 58, 237),
        "accent": (249, 115, 22),
        "type": "catapult",
        "desc": "Projette des bombes incendiaires explosives et pots de poudre sur l'ennemi"
    },
    {
        "code": "aethelis_titan",
        "alias": "forteresse_roulante_tokugawa",
        "name": "Forteresse Roulante Blindée",
        "kanji": "鉄壁",
        "clan": "tokugawa",
        "clan_color": (109, 40, 217),
        "accent": (250, 204, 21),
        "type": "mobile_fort",
        "desc": "Bastion mobile fortifié en bois massif et fer protégeant les troupes"
    },

    # --- COMMUNS / LOGISTIQUE ---
    {
        "code": "transporter_light",
        "alias": "chariot_ravitaillement_leger",
        "name": "Chariot de Ravitaillement Léger",
        "kanji": "輜重",
        "clan": "common",
        "clan_color": (22, 163, 74),     # Vert logistique
        "accent": (234, 179, 8),
        "type": "cart_light",
        "desc": "Convoi de bêtes de somme pour acheminer riz et matériaux de construction"
    },
    {
        "code": "transporter_heavy",
        "alias": "grand_convoi_logistique",
        "name": "Grand Convoi Logistique de Fief",
        "kanji": "大輸送",
        "clan": "common",
        "clan_color": (21, 128, 61),
        "accent": (251, 191, 36),
        "type": "cart_heavy",
        "desc": "Longue caravane de transporteurs convoyant d'immenses cargaisons de vivres et pierre"
    },
    {
        "code": "colony_ship",
        "alias": "expedition_etablissement_castral",
        "name": "Expédition d'Établissement Castral",
        "kanji": "開拓",
        "clan": "common",
        "clan_color": (217, 119, 6),     # Or pionnier
        "accent": (253, 224, 71),
        "type": "settler",
        "desc": "Troupe de pionniers et maîtres charpentiers pour ériger un nouveau fief"
    },
    {
        "code": "spy_probe",
        "alias": "eclaireur_shinobi_furtif",
        "name": "Éclaireur Shinobi Furtif",
        "kanji": "密偵",
        "clan": "common",
        "clan_color": (71, 85, 105),     # Gris ardoise furtif
        "accent": (148, 163, 184),
        "source": "ombre_shinobi_infiltree.jpg",
        "type": "scout",
        "desc": "Messager discret et éclaireur rapide capable d'espionner un fief en silence"
    }
]

def get_font(size):
    try:
        return ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf", size)
    except Exception:
        return ImageFont.load_default()

def get_kanji_font(size):
    try:
        # Essayer polices CJK si disponibles
        cjk_paths = [
            "/usr/share/fonts/opentype/noto/NotoSansCJK-Bold.ttc",
            "/usr/share/fonts/truetype/noto/NotoSansCJK-Bold.ttc",
            "/usr/share/fonts/truetype/droid/DroidSansFallbackFull.ttf",
            "/usr/share/fonts/truetype/freefont/FreeSansBold.ttf",
            "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf"
        ]
        for p in cjk_paths:
            if os.path.exists(p):
                return ImageFont.truetype(p, size)
    except Exception:
        pass
    return get_font(size)

def create_feudal_backdrop(width=1024, height=1024, clan="oda"):
    """Crée un fond texturé féodal dans le style parchemin washi et champ de bataille Sengoku"""
    bg_file = os.path.join(ASSETS_DIR, "epic_battle_login_bg.jpg")
    if os.path.exists(bg_file):
        bg = Image.open(bg_file).convert("RGB")
        # Recadrer au format carré 1024x1024
        bw, bh = bg.size
        min_dim = min(bw, bh)
        left = (bw - min_dim) // 2
        top = (bh - min_dim) // 2
        bg_cropped = bg.crop((left, top, left + min_dim, top + min_dim))
        bg_resized = bg_cropped.resize((width, height), Image.Resampling.LANCZOS)
        # Adoucir avec un filtre d'atmosphère
        enhancer = ImageEnhance.Color(bg_resized)
        bg_vibrant = enhancer.enhance(1.15)
        return bg_vibrant
    else:
        # Créer un dégradé washi
        im = Image.new("RGB", (width, height), (242, 232, 213))
        draw = ImageDraw.Draw(im)
        for y in range(height):
            ratio = y / height
            r = int(245 * (1 - ratio) + 210 * ratio)
            g = int(235 * (1 - ratio) + 195 * ratio)
            b = int(215 * (1 - ratio) + 175 * ratio)
            draw.line([(0, y), (width, y)], fill=(r, g, b))
        return im

def build_unit_illustration(cfg):
    target_alias_path = os.path.join(UNITS_DIR, f"{cfg['alias']}.jpg")
    target_code_path = os.path.join(UNITS_DIR, f"{cfg['code']}.jpg")

    # Si une source directe existe et qu'il suffit de la réutiliser / sublimer
    if cfg.get("source"):
        src_path = os.path.join(UNITS_DIR, cfg["source"])
        if os.path.exists(src_path):
            src_im = Image.open(src_path).convert("RGB")
            if src_im.size != (1024, 1024):
                src_im = src_im.resize((1024, 1024), Image.Resampling.LANCZOS)
            src_im.save(target_alias_path, "JPEG", quality=95)
            src_im.save(target_code_path, "JPEG", quality=95)
            print(f"[+] {cfg['code']} dupliqué depuis {cfg['source']}")
            return

    # Sinon, composer une illustration féodale riche et détaillée
    base = create_feudal_backdrop(1024, 1024, cfg["clan"])
    
    # Overlay semi-transparent pour donner l'atmosphère du clan
    clan_tint = Image.new("RGBA", (1024, 1024), (*cfg["clan_color"], 40))
    base = Image.alpha_composite(base.convert("RGBA"), clan_tint).convert("RGB")

    # Superposer des éléments de siege / atelier / cavalerie depuis tile_shipyard.png si pertinent
    shipyard_file = os.path.join(ASSETS_DIR, "tile_shipyard.png")
    if os.path.exists(shipyard_file):
        sy = Image.open(shipyard_file).convert("RGBA")
        # Découper ou intégrer des sections pertinentes selon le type
        sy_w, sy_h = sy.size
        # Zoomer sur la machine de siège ou le bâtiment
        sy_scaled = sy.resize((760, int(760 * sy_h / sy_w)), Image.Resampling.LANCZOS)
        
        # Positionner au centre bas
        paste_x = (1024 - sy_scaled.width) // 2
        paste_y = 1024 - sy_scaled.height - 80
        
        # Ombre portée
        shadow = Image.new("RGBA", (1024, 1024), (0, 0, 0, 0))
        shadow.paste(sy_scaled, (paste_x, paste_y), sy_scaled)
        shadow_blur = shadow.filter(ImageFilter.GaussianBlur(12))
        
        comp = Image.alpha_composite(base.convert("RGBA"), shadow_blur)
        comp.paste(sy_scaled, (paste_x, paste_y), sy_scaled)
        base = comp.convert("RGB")

    # Cadre calligraphique féodal & Médaillon Mon
    draw = ImageDraw.Draw(base, "RGBA")
    
    # Bordure intérieure dorée / laquée
    border_col = (*cfg["clan_color"], 220)
    draw.rectangle([(20, 20), (1004, 1004)], outline=border_col, width=5)
    draw.rectangle([(28, 28), (996, 996)], outline=(245, 158, 11, 160), width=2)
    
    # Coins ornementaux
    corner_len = 45
    for cx, cy, dx, dy in [(20, 20, 1, 1), (1004, 20, -1, 1), (20, 1004, 1, -1), (1004, 1004, -1, -1)]:
        draw.line([(cx, cy), (cx + dx * corner_len, cy)], fill=(245, 158, 11, 240), width=6)
        draw.line([(cx, cy), (cx, cy + dy * corner_len)], fill=(245, 158, 11, 240), width=6)

    # Cartouche de titre au bas de l'illustration
    cartouche_h = 130
    draw.rectangle([(30, 1024 - cartouche_h - 30), (994, 994)], fill=(15, 23, 42, 230))
    draw.rectangle([(30, 1024 - cartouche_h - 30), (994, 994)], outline=border_col, width=3)

    # Kanji Sceau d'Armée en haut à droite
    kanji = cfg["kanji"]
    kanji_font = get_kanji_font(52)
    draw.rectangle([(850, 45), (975, 155)], fill=(185, 28, 28, 210), outline=(245, 158, 11, 240), width=3)
    draw.text((912, 100), kanji, fill=(254, 240, 138), font=kanji_font, anchor="mm")

    # Textes du Cartouche : Nom de l'Unité et Rôle
    name_font = get_font(34)
    desc_font = get_font(20)
    clan_font = get_font(18)

    # Badge de Clan en haut du cartouche
    clan_label = {
        "oda": "CLAN ODA &bull; ARMEES D'OWARI",
        "takeda": "CLAN TAKEDA &bull; CAVALERIE DE KAI",
        "tokugawa": "CLAN TOKUGAWA &bull; FORTERESSES DE MIKAWA",
        "common": "LOGISTIQUE &bull; CORPS DU SHOGUNAT"
    }.get(cfg["clan"], "REGIMENT PROVINCIAL")

    draw.text((60, 1024 - cartouche_h - 15), clan_label, fill=cfg["accent"], font=clan_font)
    draw.text((60, 1024 - cartouche_h + 24), cfg["name"], fill=(255, 255, 255), font=name_font)
    draw.text((60, 1024 - cartouche_h + 68), cfg["desc"], fill=(148, 163, 184), font=desc_font)

    # Sauvegarder l'image pour l'alias et le code
    base.save(target_alias_path, "JPEG", quality=95)
    base.save(target_code_path, "JPEG", quality=95)
    print(f"[✓] {cfg['name']} ({cfg['code']}) généré avec succès -> {target_alias_path}")

print("=== GÉNÉRATION DES 13 VISUELS DE L'ATELIER DE SIÈGE & ÉCURIES ===")
for unit in UNITS_CONFIG:
    build_unit_illustration(unit)
print("=== TOUTES LES ILLUSTRATIONS SONT CRÉÉES ET DÉPLOYÉES ===")

