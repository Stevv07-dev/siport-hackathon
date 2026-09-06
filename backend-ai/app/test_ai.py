from app.ai import (
    extract_product_info,
    generate_question
)

from app.schemas import ProductState

from app.classifier import (
    determine_missing_information,
    classify_product
)


def main():

    state = ProductState()

    current_question = None
    current_field = None

    print("=" * 60)
    print("USB-C EXPORT COMPLIANCE AI")
    print("=" * 60)
    print("Ketik 'exit' untuk keluar.\n")

    while True:

        user_message = input("YOU: ")

        if user_message.lower() == "exit":
            break

        # ==================================================
        # 1. GEMINI → EXTRACT USER INFORMATION
        # ==================================================

        result = extract_product_info(
            user_message,
            state,
            current_question,
            current_field
        )

        print("\nAI EXTRACTION:")
        print(result.model_dump())

        # ==================================================
        # 2. UPDATE PRODUCT STATE
        # ==================================================

        updated_fields = result.updated_fields.model_dump(
            exclude_none=True
        )

        for field, value in updated_fields.items():

            if hasattr(state, field):

                setattr(
                    state,
                    field,
                    value
                )

        # ==================================================
        # 3. RULE ENGINE
        # ==================================================

        missing = determine_missing_information(
            state
        )

        # ==================================================
        # 4. DISPLAY CURRENT STATE
        # ==================================================

        print("\nCURRENT PRODUCT STATE:")

        print(
            state.model_dump_json(
                indent=2
            )
        )

        print("\nREQUIRED INFORMATION STILL MISSING:")

        print(missing)

        # ==================================================
        # 5. ASK NEXT QUESTION
        # ==================================================

        if missing:

            next_field = missing[0]

            next_question = generate_question(
                next_field,
                state
            )

            current_question = next_question

            print("\nAI:")
            print(next_question)

        # ==================================================
        # 6. CLASSIFICATION
        # ==================================================

        else:

            classification = classify_product(
                state
            )

            print("\nCLASSIFICATION RESULT:")

            print(classification)

            if classification["status"] == "candidate":

                print("\nAI:")
                print(
                    "Informasi klasifikasi awal sudah "
                    "cukup untuk menghasilkan kandidat."
                )

            else:

                print("\nAI:")
                print(
                    "Produk memerlukan pemeriksaan "
                    "lebih lanjut."
                )

        print("\n" + "-" * 60)


if __name__ == "__main__":
    main()