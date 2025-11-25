import sys
import cv2
import os

def detect_face(image_path):
    if not os.path.exists(image_path):
        print("error")
        return

    try:
        # 1. Load Gambar
        img = cv2.imread(image_path)
        if img is None:
            print("error")
            return

        # 2. Convert ke Grayscale
        gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)

        # 3. Load Model Wajah (Haar Cascade) bawaan OpenCV
        # cv2.data.haarcascades menunjuk ke folder data bawaan library
        cascade_path = cv2.data.haarcascades + 'haarcascade_frontalface_default.xml'
        face_cascade = cv2.CascadeClassifier(cascade_path)

        if face_cascade.empty():
            # Fallback jika path bawaan gagal (jarang terjadi)
            print("error")
            return

        # 4. Deteksi Wajah
        # scaleFactor=1.1, minNeighbors=5 adalah setting standar yang akurat
        faces = face_cascade.detectMultiScale(gray, scaleFactor=1.1, minNeighbors=5, minSize=(30, 30))

        # 5. Output Hasil
        if len(faces) > 0:
            print("true")
        else:
            print("false")

    except Exception as e:
        # Print error untuk debugging di log Laravel jika perlu
        # print(str(e))
        print("error")

if __name__ == "__main__":
    if len(sys.argv) > 1:
        detect_face(sys.argv[1])
    else:
        print("error")
