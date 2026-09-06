from .schemas import ProductState


def determine_missing_information(
    state: ProductState
):
    """
    Menentukan informasi yang WAJIB tersedia
    untuk melanjutkan proses klasifikasi USB-C cable.

    Tidak semua field dalam ProductState wajib diisi.
    """

    missing = []

    # --------------------------------------------------
    # LEVEL 1 — Identifikasi barang
    # --------------------------------------------------

    if not state.product_type:
        missing.append("product_type")

    # --------------------------------------------------
    # LEVEL 2 — Karakteristik kabel
    # --------------------------------------------------

    if state.connector_present is None:
        missing.append("connector_present")

    # Jika ada connector, kita perlu tahu jenisnya
    if state.connector_present is True:

        if not state.connector_type:
            missing.append("connector_type")

        if state.voltage_rating_v is None:
            missing.append("voltage_rating_v")

    # --------------------------------------------------
    # LEVEL 3 — Karakteristik material
    # --------------------------------------------------

    if state.insulation_material is None:
        missing.append("insulation_material")

    # --------------------------------------------------
    # LEVEL 4 — Karakteristik konduktor
    # --------------------------------------------------

    if state.core_diameter_mm is None:
        missing.append("core_diameter_mm")

    return missing


def classify_product(
    state: ProductState
):

    missing = determine_missing_information(state)

    if missing:

        return {
            "status": "need_information",
            "missing": missing
        }

    # --------------------------------------------------
    # Candidate classification
    # --------------------------------------------------

    if (
        state.connector_present is True
        and state.voltage_rating_v <= 1000
    ):

        return {
            "status": "candidate",
            "candidate_heading": "8544",
            "candidate_subheading": "8544.42",
            "confidence": "candidate",
            "note": (
                "Ini merupakan kandidat klasifikasi "
                "dan bukan penetapan HS Code resmi."
            )
        }

    return {
        "status": "needs_review",
        "candidate_heading": None,
        "candidate_subheading": None,
        "reason": (
            "Karakteristik produk belum sesuai "
            "dengan rule prototype."
        )
    }