export const getFilename = (filePath: string) => {
    const lastPart = filePath.split("/").pop();

    if (!lastPart) return "";
    
    return lastPart
        .replace(/\.[^/.]+$/, "")
        .replace("-", " ")
        .replace(/^./, c => c.toUpperCase());
};
