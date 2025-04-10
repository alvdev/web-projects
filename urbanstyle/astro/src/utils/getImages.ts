export async function getGalleryImages(galleryId: string) {
  // 1. List all album files from collections path
  const images = import.meta.glob<{ default: ImageMetadata }>(
    "/src/content/galleries/**/*.{jpeg,jpg,webp,png}",
    { import: "default", eager: true }
  );

  // 2. Filter and transform images by albumId
  const filteredImages = Object.entries(images)
    .filter(([key]) => key.includes(galleryId))
    .map(([_, image]) => image);

  // 3. Shuffle images in random order
  filteredImages.sort(() => Math.random() - 0.5);
  
  return filteredImages;
}
