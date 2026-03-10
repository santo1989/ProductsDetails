USE products_db;

-- Update existing sample products with Unsplash demo image URLs
-- Run this if you already imported data and want presentable demo images

UPDATE products
SET
    Main_Image = 'https://unsplash.com/photos/5E5N49RWtbA/download?force=true&w=1200',
    Image1 = 'https://unsplash.com/photos/mp0bgAAfoUs/download?force=true&w=1200',
    Image2 = 'https://unsplash.com/photos/RiDxDgHg7pw/download?force=true&w=1200',
    Image3 = 'https://unsplash.com/photos/yCdPU73kGSc/download?force=true&w=1200',
    Image4 = 'https://unsplash.com/photos/DgXIq5tTUqY/download?force=true&w=1200'
WHERE Product_URL = 'premium-cotton-tshirt';

UPDATE products
SET
    Main_Image = 'https://unsplash.com/photos/Lks7vei-eAg/download?force=true&w=1200',
    Image1 = 'https://unsplash.com/photos/SJvDxw0azqw/download?force=true&w=1200',
    Image2 = 'https://unsplash.com/photos/qqRGHREFJJc/download?force=true&w=1200',
    Image3 = 'https://unsplash.com/photos/R3LcfTvcGWY/download?force=true&w=1200',
    Image4 = 'https://unsplash.com/photos/90WdFgbf59w/download?force=true&w=1200'
WHERE Product_URL = 'denim-slim-fit-jeans';

UPDATE products
SET
    Main_Image = 'https://unsplash.com/photos/T7K4aEPoGGk/download?force=true&w=1200',
    Image1 = 'https://unsplash.com/photos/6anudmpILw4/download?force=true&w=1200',
    Image2 = 'https://unsplash.com/photos/Yc5sL5MCbEA/download?force=true&w=1200',
    Image3 = 'https://unsplash.com/photos/cYyqhdbJ9TI/download?force=true&w=1200',
    Image4 = 'https://unsplash.com/photos/OW5KP_Pj85Q/download?force=true&w=1200'
WHERE Product_URL = 'formal-dress-shirt';

UPDATE products
SET
    Main_Image = 'https://unsplash.com/photos/jgWZM5TXnwE/download?force=true&w=1200',
    Image1 = 'https://unsplash.com/photos/FQgI8AD-BSg/download?force=true&w=1200',
    Image2 = 'https://unsplash.com/photos/VxrFNhSI9zk/download?force=true&w=1200',
    Image3 = 'https://unsplash.com/photos/LvSUaq_yhBY/download?force=true&w=1200',
    Image4 = 'https://unsplash.com/photos/Zb0hcV0nJFE/download?force=true&w=1200'
WHERE Product_URL = 'athletic-performance-polo';

UPDATE products
SET
    Main_Image = 'https://unsplash.com/photos/xPJYL0l5Ii8/download?force=true&w=1200',
    Image1 = 'https://unsplash.com/photos/OvW_Eh0KF10/download?force=true&w=1200',
    Image2 = 'https://unsplash.com/photos/XnC5eO2WFh8/download?force=true&w=1200',
    Image3 = 'https://unsplash.com/photos/MVGxDHj3c7o/download?force=true&w=1200',
    Image4 = 'https://unsplash.com/photos/hR535Mow9_E/download?force=true&w=1200'
WHERE Product_URL = 'winter-wool-sweater';

UPDATE products
SET
    Main_Image = 'https://unsplash.com/photos/WWesmHEgXDs/download?force=true&w=1200',
    Image1 = 'https://unsplash.com/photos/HRZUzoX1e6w/download?force=true&w=1200',
    Image2 = 'https://unsplash.com/photos/Y_aXcZ4VLqI/download?force=true&w=1200',
    Image3 = 'https://unsplash.com/photos/YWX9z_fcG0o/download?force=true&w=1200',
    Image4 = 'https://unsplash.com/photos/8lnbXtxFGZw/download?force=true&w=1200'
WHERE Product_URL = 'casual-hooded-sweatshirt';

UPDATE products
SET
    Main_Image = 'https://unsplash.com/photos/EuDapbwpPmA/download?force=true&w=1200',
    Image1 = 'https://unsplash.com/photos/vCF5sB7QecM/download?force=true&w=1200',
    Image2 = 'https://unsplash.com/photos/iMdsjoiftZo/download?force=true&w=1200',
    Image3 = 'https://unsplash.com/photos/zWTGZOe3YBo/download?force=true&w=1200',
    Image4 = 'https://unsplash.com/photos/H7B-M3HQbgE/download?force=true&w=1200'
WHERE Product_URL = 'linen-summer-shorts';
