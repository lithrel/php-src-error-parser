module Search exposing (filterBy)

import Element exposing (Element, text, alignBottom)
import Element.Input as Input
import ErrorCall exposing (ErrorCall)

--view : (String -> msg) -> Element msg
--view searchTyped =
--    Input.text [ ]
--        { onChange = searchTyped
--        , text = ""
--        , placeholder = Nothing
--        , label = Input.labelLeft [ alignBottom ] (text "Search: ")
--        }

filterBy : List ErrorCall -> String -> List ErrorCall
filterBy errors search =
    if String.isEmpty search then
        errors
    else
        List.filter
            (\e ->
                (matchMessage e search)
                || (matchLevel e search)
                || (matchFunction e search)
                || (matchFile e search)
                )
            errors

matchMessage : ErrorCall -> String -> Bool
matchMessage error search =
    String.contains (String.toLower search) (String.toLower error.message)

matchLevel : ErrorCall -> String -> Bool
matchLevel error search =
    not <| List.isEmpty
        <| List.filter (\l -> String.contains search l)
        <| List.map (\o -> o.level) error.occ

matchFunction : ErrorCall-> String -> Bool
matchFunction error search =
    let
        functions =
            List.map (\o -> o.function) error.occ
    in
    not (List.isEmpty (List.filter (\l -> String.contains search l) functions))

matchFile : ErrorCall-> String -> Bool
matchFile error search =
    let
        files =
            List.map (\o -> o.file) error.occ
    in
    not (List.isEmpty (List.filter (\l -> String.contains search l) files))
