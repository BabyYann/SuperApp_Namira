/**
 * Utility to compress image files before uploading.
 * Reduces raw 5-15MB camera photos to lightweight ~150-300KB JPEGs.
 * Uses URL.createObjectURL to avoid mobile device memory issues and base64 bloat.
 */
export const compressImageFile = (file, maxDim = 1280, quality = 0.75) => {
    return new Promise((resolve) => {
        if (!file || !file.type || !file.type.startsWith('image/')) {
            resolve(file);
            return;
        }

        const objectUrl = URL.createObjectURL(file);
        const img = new Image();

        img.onload = () => {
            URL.revokeObjectURL(objectUrl);
            let width = img.width;
            let height = img.height;

            if (width > height && width > maxDim) {
                height = Math.round((height * maxDim) / width);
                width = maxDim;
            } else if (height > maxDim) {
                width = Math.round((width * maxDim) / height);
                height = maxDim;
            }

            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;

            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, width, height);

            canvas.toBlob(
                (blob) => {
                    if (blob) {
                        const cleanName = (file.name || 'image').replace(/\.[^/.]+$/, '') + '.jpg';
                        const compressedFile = new File([blob], cleanName, {
                            type: 'image/jpeg',
                            lastModified: Date.now(),
                        });
                        resolve(compressedFile);
                    } else {
                        // Fallback via toDataURL if canvas.toBlob returns null
                        try {
                            const dataUrl = canvas.toDataURL('image/jpeg', quality);
                            const arr = dataUrl.split(',');
                            const mime = arr[0].match(/:(.*?);/)[1];
                            const bstr = atob(arr[1]);
                            let n = bstr.length;
                            const u8arr = new Uint8Array(n);
                            while (n--) {
                                u8arr[n] = bstr.charCodeAt(n);
                            }
                            const cleanName = (file.name || 'image').replace(/\.[^/.]+$/, '') + '.jpg';
                            resolve(new File([u8arr], cleanName, { type: mime, lastModified: Date.now() }));
                        } catch (e) {
                            resolve(file);
                        }
                    }
                },
                'image/jpeg',
                quality
            );
        };

        img.onerror = () => {
            URL.revokeObjectURL(objectUrl);
            resolve(file);
        };

        img.src = objectUrl;
    });
};
