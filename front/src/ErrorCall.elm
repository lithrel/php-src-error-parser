module ErrorCall exposing
    ( ErrorCall
    --, Hugger
    , Occurence
    , decoder
    )

import Json.Decode as Decode exposing (Decoder, list, string, int, null, nullable)
import Json.Decode.Pipeline exposing (optional, required)


-- TYPES

type alias ErrorCall =
    { message : String
    , occ : List Occurence
    , hugger : String
    }

--type alias Hugger =
--    { main : String
--    , hints : List String
--    , more : List String
--    }

type alias Occurence =
    { file : String
    , line : Int
    , function : String -- ZendFunction
    , exception : String -- ZendException
    , level : String -- ErrorLevel
    , args : List String
    }


-- DECODERS

decoder : Decoder ErrorCall
decoder =
    Decode.succeed ErrorCall
        |> required "message" string
        |> required "occ" (list occDecoder)
        |> optional "hugger" string "No description available."
        --|> optional "hugger" huggerDecoder (Hugger "No description given." [] [])

occDecoder : Decoder Occurence
occDecoder =
    Decode.succeed Occurence
        |> required "file" string
        |> required "line" int
        |> required "function" string
        |> optional "exception" string ""
        |> optional "level" string ""
        |> required "args" (list string)

--huggerDecoder : Decoder Hugger
--huggerDecoder =
--    Decode.succeed Hugger
--        |> required "main" string
--        |> optional "hints" (list string) []
--        |> optional "more" (list string) []
